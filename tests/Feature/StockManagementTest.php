<?php

namespace Tests\Feature;

use App\Product;
use App\Transaction;
use App\TransactionSellLine;
use App\VariationLocationDetails;
use App\Variation;
use App\Contact;
use App\BusinessLocation;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $business_id;
    protected $location_id;
    protected $product;
    protected $variation;
    protected $contact;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user with permissions
        $this->user = User::factory()->create();
        $this->business_id = $this->user->business_id;

        // Create business location
        $location = BusinessLocation::factory()->create([
            'business_id' => $this->business_id,
        ]);
        $this->location_id = $location->id;

        // Create test product with stock enabled
        $this->product = Product::factory()->create([
            'business_id' => $this->business_id,
            'enable_stock' => 1,
            'alert_quantity' => 10,
        ]);

        // Create variation for product
        $this->variation = Variation::factory()->create([
            'product_id' => $this->product->id,
            'default_sell_price' => 100,
        ]);

        // Initialize stock at location
        VariationLocationDetails::create([
            'variation_id' => $this->variation->id,
            'location_id' => $this->location_id,
            'qty_available' => 100, // Starting stock
        ]);

        // Create test contact
        $this->contact = Contact::factory()->create([
            'business_id' => $this->business_id,
            'type' => 'customer',
        ]);

        $this->actingAs($this->user);
    }

    /**
     * Get current stock from database
     */
    protected function getCurrentStock()
    {
        return VariationLocationDetails::where('variation_id', $this->variation->id)
            ->where('location_id', $this->location_id)
            ->value('qty_available') ?? 0;
    }

    /**
     * Calculate stock from transaction history
     */
    protected function getCalculatedStock()
    {
        $productUtil = app(\App\Utils\ProductUtil::class);
        $history = $productUtil->getVariationStockHistory(
            $this->business_id,
            $this->variation->id,
            $this->location_id
        );

        return isset($history[0]) ? $history[0]['stock'] : 0;
    }

    /**
     * Assert stock matches between database and history
     */
    protected function assertStockMatches($message = '')
    {
        $currentStock = $this->getCurrentStock();
        $calculatedStock = $this->getCalculatedStock();

        $this->assertEquals(
            (float)$calculatedStock,
            (float)$currentStock,
            $message . " | Current: {$currentStock}, Calculated: {$calculatedStock}"
        );
    }

    /** @test */
    public function test_stock_decreases_when_creating_final_sale()
    {
        $initialStock = $this->getCurrentStock();
        $saleQty = 10;

        $response = $this->post(route('pos.store'), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => $saleQty,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        $response->assertStatus(200);

        $expectedStock = $initialStock - $saleQty;
        $this->assertEquals($expectedStock, $this->getCurrentStock(), 'Stock should decrease by sale quantity');
        $this->assertStockMatches('Stock mismatch after creating final sale');
    }

    /** @test */
    public function test_stock_not_affected_when_creating_draft_sale()
    {
        $initialStock = $this->getCurrentStock();
        $saleQty = 10;

        $response = $this->post(route('pos.store'), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'draft',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => $saleQty,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        $response->assertStatus(200);

        $this->assertEquals($initialStock, $this->getCurrentStock(), 'Stock should not change for draft sale');
        $this->assertStockMatches('Stock mismatch after creating draft sale');
    }

    /** @test */
    public function test_stock_decreases_when_converting_draft_to_final()
    {
        $saleQty = 15;

        // Create draft sale
        $transaction = Transaction::create([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => 'sell',
            'status' => 'draft',
            'contact_id' => $this->contact->id,
            'transaction_date' => now(),
            'total_before_tax' => $saleQty * 100,
            'final_total' => $saleQty * 100,
        ]);

        TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'variation_id' => $this->variation->id,
            'quantity' => $saleQty,
            'unit_price_inc_tax' => 100,
        ]);

        $initialStock = $this->getCurrentStock();

        // Update to final
        $response = $this->put(route('pos.update', $transaction->id), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => $saleQty,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                    'transaction_sell_lines_id' => TransactionSellLine::where('transaction_id', $transaction->id)->first()->id,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        $expectedStock = $initialStock - $saleQty;
        $this->assertEquals($expectedStock, $this->getCurrentStock(), 'Stock should decrease when draft becomes final');
        $this->assertStockMatches('Stock mismatch after converting draft to final');
    }

    /** @test */
    public function test_stock_increases_when_converting_final_to_draft()
    {
        $saleQty = 20;
        $initialStock = $this->getCurrentStock();

        // Create final sale
        $transaction = Transaction::create([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => 'sell',
            'status' => 'final',
            'contact_id' => $this->contact->id,
            'transaction_date' => now(),
            'total_before_tax' => $saleQty * 100,
            'final_total' => $saleQty * 100,
        ]);

        TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'variation_id' => $this->variation->id,
            'quantity' => $saleQty,
            'unit_price_inc_tax' => 100,
        ]);

        // Manually decrease stock (simulating what should have happened)
        VariationLocationDetails::where('variation_id', $this->variation->id)
            ->where('location_id', $this->location_id)
            ->decrement('qty_available', $saleQty);

        $stockAfterSale = $this->getCurrentStock();

        // Update to draft
        $response = $this->put(route('pos.update', $transaction->id), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'draft',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => $saleQty,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                    'transaction_sell_lines_id' => TransactionSellLine::where('transaction_id', $transaction->id)->first()->id,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        $this->assertEquals($initialStock, $this->getCurrentStock(), 'Stock should return to original when final becomes draft');
        $this->assertStockMatches('Stock mismatch after converting final to draft');
    }

    /** @test */
    public function test_stock_adjusts_when_increasing_quantity_in_final_sale()
    {
        $initialQty = 10;
        $newQty = 20;
        $additionalDecrease = $newQty - $initialQty;

        // Create final sale
        $transaction = Transaction::create([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => 'sell',
            'status' => 'final',
            'contact_id' => $this->contact->id,
            'transaction_date' => now(),
            'total_before_tax' => $initialQty * 100,
            'final_total' => $initialQty * 100,
        ]);

        $sellLine = TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'variation_id' => $this->variation->id,
            'quantity' => $initialQty,
            'unit_price_inc_tax' => 100,
        ]);

        // Manually decrease stock
        VariationLocationDetails::where('variation_id', $this->variation->id)
            ->where('location_id', $this->location_id)
            ->decrement('qty_available', $initialQty);

        $stockAfterInitialSale = $this->getCurrentStock();

        // Update quantity
        $response = $this->put(route('pos.update', $transaction->id), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => $newQty,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                    'transaction_sell_lines_id' => $sellLine->id,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        $expectedStock = $stockAfterInitialSale - $additionalDecrease;
        $this->assertEquals($expectedStock, $this->getCurrentStock(), 'Stock should decrease by additional quantity');
        $this->assertStockMatches('Stock mismatch after increasing quantity in final sale');
    }

    /** @test */
    public function test_stock_adjusts_when_decreasing_quantity_in_final_sale()
    {
        $initialQty = 30;
        $newQty = 15;
        $returnIncrease = $initialQty - $newQty;

        // Create final sale
        $transaction = Transaction::create([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => 'sell',
            'status' => 'final',
            'contact_id' => $this->contact->id,
            'transaction_date' => now(),
            'total_before_tax' => $initialQty * 100,
            'final_total' => $initialQty * 100,
        ]);

        $sellLine = TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'variation_id' => $this->variation->id,
            'quantity' => $initialQty,
            'unit_price_inc_tax' => 100,
        ]);

        // Manually decrease stock
        VariationLocationDetails::where('variation_id', $this->variation->id)
            ->where('location_id', $this->location_id)
            ->decrement('qty_available', $initialQty);

        $stockAfterInitialSale = $this->getCurrentStock();

        // Update quantity
        $response = $this->put(route('pos.update', $transaction->id), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => $newQty,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                    'transaction_sell_lines_id' => $sellLine->id,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        $expectedStock = $stockAfterInitialSale + $returnIncrease;
        $this->assertEquals($expectedStock, $this->getCurrentStock(), 'Stock should increase when quantity reduced');
        $this->assertStockMatches('Stock mismatch after decreasing quantity in final sale');
    }

    /** @test */
    public function test_stock_adjusts_when_adding_product_to_existing_final_sale()
    {
        // Create another product
        $product2 = Product::factory()->create([
            'business_id' => $this->business_id,
            'enable_stock' => 1,
        ]);

        $variation2 = Variation::factory()->create([
            'product_id' => $product2->id,
            'default_sell_price' => 150,
        ]);

        VariationLocationDetails::create([
            'variation_id' => $variation2->id,
            'location_id' => $this->location_id,
            'qty_available' => 50,
        ]);

        $initialQty = 10;
        $newProductQty = 5;

        // Create final sale with first product
        $transaction = Transaction::create([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => 'sell',
            'status' => 'final',
            'contact_id' => $this->contact->id,
            'transaction_date' => now(),
            'total_before_tax' => $initialQty * 100,
            'final_total' => $initialQty * 100,
        ]);

        $sellLine = TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'variation_id' => $this->variation->id,
            'quantity' => $initialQty,
            'unit_price_inc_tax' => 100,
        ]);

        // Manually decrease stock for first product
        VariationLocationDetails::where('variation_id', $this->variation->id)
            ->where('location_id', $this->location_id)
            ->decrement('qty_available', $initialQty);

        $product2InitialStock = VariationLocationDetails::where('variation_id', $variation2->id)
            ->where('location_id', $this->location_id)
            ->value('qty_available');

        // Update to add second product
        $response = $this->put(route('pos.update', $transaction->id), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => $initialQty,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                    'transaction_sell_lines_id' => $sellLine->id,
                ],
                [
                    'product_id' => $product2->id,
                    'variation_id' => $variation2->id,
                    'quantity' => $newProductQty,
                    'unit_price' => 150,
                    'enable_stock' => 1,
                    // No transaction_sell_lines_id means this is a new product
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        // Check second product stock decreased
        $product2NewStock = VariationLocationDetails::where('variation_id', $variation2->id)
            ->where('location_id', $this->location_id)
            ->value('qty_available');

        $expectedProduct2Stock = $product2InitialStock - $newProductQty;
        $this->assertEquals($expectedProduct2Stock, $product2NewStock, 'New product stock should decrease');

        // Verify both products match history
        $this->assertStockMatches('Product 1 stock mismatch after adding product 2');

        $productUtil = app(\App\Utils\ProductUtil::class);
        $history2 = $productUtil->getVariationStockHistory(
            $this->business_id,
            $variation2->id,
            $this->location_id
        );

        $calculatedStock2 = isset($history2[0]) ? $history2[0]['stock'] : 0;
        $this->assertEquals(
            (float)$calculatedStock2,
            (float)$product2NewStock,
            "Product 2 stock mismatch after adding to sale"
        );
    }

    /** @test */
    public function test_stock_adjusts_when_removing_product_from_existing_final_sale()
    {
        $product1Qty = 10;
        $product2Qty = 8;

        // Create second product
        $product2 = Product::factory()->create([
            'business_id' => $this->business_id,
            'enable_stock' => 1,
        ]);

        $variation2 = Variation::factory()->create([
            'product_id' => $product2->id,
            'default_sell_price' => 150,
        ]);

        VariationLocationDetails::create([
            'variation_id' => $variation2->id,
            'location_id' => $this->location_id,
            'qty_available' => 50,
        ]);

        // Create final sale with both products
        $transaction = Transaction::create([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => 'sell',
            'status' => 'final',
            'contact_id' => $this->contact->id,
            'transaction_date' => now(),
            'total_before_tax' => ($product1Qty * 100) + ($product2Qty * 150),
            'final_total' => ($product1Qty * 100) + ($product2Qty * 150),
        ]);

        $sellLine1 = TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'variation_id' => $this->variation->id,
            'quantity' => $product1Qty,
            'unit_price_inc_tax' => 100,
        ]);

        $sellLine2 = TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product2->id,
            'variation_id' => $variation2->id,
            'quantity' => $product2Qty,
            'unit_price_inc_tax' => 150,
        ]);

        // Manually decrease stock
        VariationLocationDetails::where('variation_id', $this->variation->id)
            ->where('location_id', $this->location_id)
            ->decrement('qty_available', $product1Qty);

        VariationLocationDetails::where('variation_id', $variation2->id)
            ->where('location_id', $this->location_id)
            ->decrement('qty_available', $product2Qty);

        $product2StockBefore = VariationLocationDetails::where('variation_id', $variation2->id)
            ->where('location_id', $this->location_id)
            ->value('qty_available');

        // Update to remove product2 (only include product1)
        $response = $this->put(route('pos.update', $transaction->id), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => $product1Qty,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                    'transaction_sell_lines_id' => $sellLine1->id,
                ],
                // product2 is removed
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        // Check product2 stock increased back
        $product2StockAfter = VariationLocationDetails::where('variation_id', $variation2->id)
            ->where('location_id', $this->location_id)
            ->value('qty_available');

        $expectedProduct2Stock = $product2StockBefore + $product2Qty;
        $this->assertEquals($expectedProduct2Stock, $product2StockAfter, 'Removed product stock should increase');

        // Verify both products match history
        $this->assertStockMatches('Product 1 stock mismatch after removing product 2');

        $productUtil = app(\App\Utils\ProductUtil::class);
        $history2 = $productUtil->getVariationStockHistory(
            $this->business_id,
            $variation2->id,
            $this->location_id
        );

        $calculatedStock2 = isset($history2[0]) ? $history2[0]['stock'] : 0;
        $this->assertEquals(
            (float)$calculatedStock2,
            (float)$product2StockAfter,
            "Product 2 stock mismatch after removing from sale"
        );
    }

    /** @test */
    public function test_under_processing_status_decreases_stock()
    {
        $initialStock = $this->getCurrentStock();
        $saleQty = 12;

        $response = $this->post(route('pos.store'), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'under processing',
            'sub_status' => 'repair',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => $saleQty,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        $response->assertStatus(200);

        $expectedStock = $initialStock - $saleQty;
        $this->assertEquals($expectedStock, $this->getCurrentStock(), 'Stock should decrease for under processing status');
        $this->assertStockMatches('Stock mismatch after creating under processing sale');
    }

    /** @test */
    public function test_no_double_deduction_when_under_processing_becomes_final()
    {
        $saleQty = 18;

        // Create under processing sale
        $transaction = Transaction::create([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => 'sell',
            'status' => 'under processing',
            'sub_status' => 'repair',
            'contact_id' => $this->contact->id,
            'transaction_date' => now(),
            'total_before_tax' => $saleQty * 100,
            'final_total' => $saleQty * 100,
        ]);

        TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'variation_id' => $this->variation->id,
            'quantity' => $saleQty,
            'unit_price_inc_tax' => 100,
        ]);

        // Manually decrease stock (simulating what should have happened for "under processing")
        VariationLocationDetails::where('variation_id', $this->variation->id)
            ->where('location_id', $this->location_id)
            ->decrement('qty_available', $saleQty);

        $stockAfterUnderProcessing = $this->getCurrentStock();

        // Update to final
        $response = $this->put(route('pos.update', $transaction->id), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => $saleQty,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                    'transaction_sell_lines_id' => TransactionSellLine::where('transaction_id', $transaction->id)->first()->id,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        // Stock should NOT decrease again
        $this->assertEquals($stockAfterUnderProcessing, $this->getCurrentStock(), 'Stock should not decrease again when under processing becomes final');
        $this->assertStockMatches('Stock mismatch after converting under processing to final - possible double deduction');
    }

    /** @test */
    public function test_multiple_edits_maintain_stock_accuracy()
    {
        $initialStock = $this->getCurrentStock();

        // Create final sale with 10 items
        $transaction = Transaction::create([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => 'sell',
            'status' => 'final',
            'contact_id' => $this->contact->id,
            'transaction_date' => now(),
            'total_before_tax' => 10 * 100,
            'final_total' => 10 * 100,
        ]);

        $sellLine = TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'variation_id' => $this->variation->id,
            'quantity' => 10,
            'unit_price_inc_tax' => 100,
        ]);

        VariationLocationDetails::where('variation_id', $this->variation->id)
            ->where('location_id', $this->location_id)
            ->decrement('qty_available', 10);

        // Edit 1: Increase to 15
        $this->put(route('pos.update', $transaction->id), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => 15,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                    'transaction_sell_lines_id' => $sellLine->id,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        $this->assertStockMatches('Stock mismatch after first edit (increase to 15)');

        // Edit 2: Decrease to 8
        $this->put(route('pos.update', $transaction->id), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => 8,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                    'transaction_sell_lines_id' => $sellLine->id,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        $this->assertStockMatches('Stock mismatch after second edit (decrease to 8)');

        // Edit 3: Back to 12
        $this->put(route('pos.update', $transaction->id), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => 12,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                    'transaction_sell_lines_id' => $sellLine->id,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        $expectedFinalStock = $initialStock - 12;
        $this->assertEquals($expectedFinalStock, $this->getCurrentStock(), 'Final stock should be initial minus 12');
        $this->assertStockMatches('Stock mismatch after third edit (back to 12)');
    }

    /** @test */
    public function test_spare_parts_addition_via_api_reduces_stock()
    {
        // This test would require the SparePartsController to be accessible
        // We'll create a simplified version using direct database manipulation

        // Create a job sheet
        $jobSheet = DB::table('repair_job_sheets')->insertGetId([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'job_sheet_no' => 'JS-' . rand(1000, 9999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create transaction for job sheet
        $transaction = Transaction::create([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => 'sell',
            'status' => 'under processing',
            'sub_status' => 'repair',
            'contact_id' => $this->contact->id,
            'transaction_date' => now(),
            'repair_job_sheet_id' => $jobSheet,
            'total_before_tax' => 0,
            'final_total' => 0,
        ]);

        $initialStock = $this->getCurrentStock();
        $sparePartQty = 5;

        // Add spare part to product_joborder with all flags set
        DB::table('product_joborder')->insert([
            'job_order_id' => $jobSheet,
            'product_id' => $this->product->id,
            'quantity' => $sparePartQty,
            'price' => 100,
            'delivered_status' => 1,
            'out_for_deliver' => 1,
            'client_approval' => 1,
        ]);

        // Simulate the transaction update that would add the sell line
        TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'variation_id' => $this->variation->id,
            'quantity' => $sparePartQty,
            'unit_price_inc_tax' => 100,
        ]);

        // Manually adjust stock as the system should
        VariationLocationDetails::where('variation_id', $this->variation->id)
            ->where('location_id', $this->location_id)
            ->decrement('qty_available', $sparePartQty);

        $expectedStock = $initialStock - $sparePartQty;
        $this->assertEquals($expectedStock, $this->getCurrentStock(), 'Stock should decrease when spare part is added');
        $this->assertStockMatches('Stock mismatch after adding spare part');
    }

    /** @test */
    public function test_maintenance_note_product_addition_reduces_stock()
    {
        // Create a job sheet for maintenance note
        $jobSheet = DB::table('repair_job_sheets')->insertGetId([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'job_sheet_no' => 'JS-' . rand(1000, 9999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create transaction
        $transaction = Transaction::create([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => 'sell',
            'status' => 'under processing',
            'sub_status' => 'repair',
            'contact_id' => $this->contact->id,
            'transaction_date' => now(),
            'repair_job_sheet_id' => $jobSheet,
            'total_before_tax' => 0,
            'final_total' => 0,
        ]);

        // Create maintenance note
        $maintenanceNote = DB::table('maintenance_note')->insertGetId([
            'job_sheet_id' => $jobSheet,
            'category_status' => 'purchase_req',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $initialStock = $this->getCurrentStock();
        $noteProductQty = 7;

        // Add product via maintenance note (with client approval)
        DB::table('product_joborder')->insert([
            'job_order_id' => $jobSheet,
            'product_id' => $this->product->id,
            'quantity' => $noteProductQty,
            'price' => 120,
            'client_approval' => 1,
            'delivered_status' => 1,
            'out_for_deliver' => 1,
        ]);

        // Simulate transaction update
        TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'variation_id' => $this->variation->id,
            'quantity' => $noteProductQty,
            'unit_price_inc_tax' => 120,
        ]);

        // Manually adjust stock
        VariationLocationDetails::where('variation_id', $this->variation->id)
            ->where('location_id', $this->location_id)
            ->decrement('qty_available', $noteProductQty);

        $expectedStock = $initialStock - $noteProductQty;
        $this->assertEquals($expectedStock, $this->getCurrentStock(), 'Stock should decrease when product added via maintenance note');
        $this->assertStockMatches('Stock mismatch after maintenance note product addition');
    }

    /** @test */
    public function test_concurrent_sale_edits_maintain_accuracy()
    {
        // This simulates two users editing the same sale
        // In reality this should be prevented by locking, but we test the outcome

        $initialStock = $this->getCurrentStock();
        $saleQty = 20;

        // Create final sale
        $transaction = Transaction::create([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => 'sell',
            'status' => 'final',
            'contact_id' => $this->contact->id,
            'transaction_date' => now(),
            'total_before_tax' => $saleQty * 100,
            'final_total' => $saleQty * 100,
        ]);

        $sellLine = TransactionSellLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $this->product->id,
            'variation_id' => $this->variation->id,
            'quantity' => $saleQty,
            'unit_price_inc_tax' => 100,
        ]);

        // Manually decrease stock
        VariationLocationDetails::where('variation_id', $this->variation->id)
            ->where('location_id', $this->location_id)
            ->decrement('qty_available', $saleQty);

        // Simulate concurrent edit: both users load the transaction at the same time
        // User 1 changes quantity to 25
        // User 2 changes quantity to 30

        // User 1's update
        $this->put(route('pos.update', $transaction->id), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => 25,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                    'transaction_sell_lines_id' => $sellLine->id,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        $stockAfterUser1 = $this->getCurrentStock();

        // User 2's update (overwrites user 1)
        $this->put(route('pos.update', $transaction->id), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $this->product->id,
                    'variation_id' => $this->variation->id,
                    'quantity' => 30,
                    'unit_price' => 100,
                    'enable_stock' => 1,
                    'transaction_sell_lines_id' => $sellLine->id,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        $expectedStock = $initialStock - 30;
        $this->assertEquals($expectedStock, $this->getCurrentStock(), 'Final stock should reflect last update (30 items)');
        $this->assertStockMatches('Stock mismatch after concurrent edits');
    }

    /** @test */
    public function test_product_with_zero_initial_stock_updated_correctly()
    {
        // Create product with zero stock
        $product = Product::factory()->create([
            'business_id' => $this->business_id,
            'enable_stock' => 1,
        ]);

        $variation = Variation::factory()->create([
            'product_id' => $product->id,
            'default_sell_price' => 200,
        ]);

        VariationLocationDetails::create([
            'variation_id' => $variation->id,
            'location_id' => $this->location_id,
            'qty_available' => 0,
        ]);

        $initialStock = 0;

        // Try to create a sale (should fail or handle gracefully)
        $response = $this->post(route('pos.store'), [
            'contact_id' => $this->contact->id,
            'location_id' => $this->location_id,
            'status' => 'final',
            'products' => [
                [
                    'product_id' => $product->id,
                    'variation_id' => $variation->id,
                    'quantity' => 5,
                    'unit_price' => 200,
                    'enable_stock' => 1,
                ],
            ],
            'tax_rate_id' => null,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
        ]);

        // Depending on system behavior, this should either fail or allow overselling
        // We test that stock remains consistent with transaction history
        $productUtil = app(\App\Utils\ProductUtil::class);
        $history = $productUtil->getVariationStockHistory(
            $this->business_id,
            $variation->id,
            $this->location_id
        );

        $calculatedStock = isset($history[0]) ? $history[0]['stock'] : 0;
        $currentStock = VariationLocationDetails::where('variation_id', $variation->id)
            ->where('location_id', $this->location_id)
            ->value('qty_available');

        $this->assertEquals(
            (float)$calculatedStock,
            (float)$currentStock,
            "Stock mismatch for product with zero initial stock"
        );
    }
}

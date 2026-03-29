<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBackgroundTask;
use App\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAIProductUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:process-ai-updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process AI updates for products with ai_flag set to true';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->info('Starting AI product updates process...');
            Log::info('Starting scheduled AI product updates');

            // Get products with ai_flag set to true
            $products = Product::where('ai_flag', true)
                ->limit(10) // Process 10 products at a time to avoid overloading
                ->get();

            if ($products->isEmpty()) {
                $this->info('No products found with ai_flag set to true.');
                Log::info('No products found with ai_flag set to true');
                return 0;
            }

            $this->info('Found ' . $products->count() . ' products to process.');
            Log::info('Found ' . $products->count() . ' products to process');

            foreach ($products as $product) {
                $this->info('Processing product: ' . $product->name . ' (SKU: ' . $product->sku . ')');
                
                try {
                    // Prepare data for the job
                    $data = [
                        'business_id' => $product->business_id,
                        'user_id' => $product->created_by,
                        'sku' => $product->sku,
                        'product_name' => $product->name
                    ];
                    
                    // Dispatch the job
                    ProcessBackgroundTask::dispatch($data);
                    
                    $this->info('Successfully dispatched job for product: ' . $product->name);
                    Log::info('Successfully dispatched job for product', [
                        'sku' => $product->sku,
                        'product_id' => $product->id
                    ]);
                } catch (\Exception $e) {
                    $this->error('Error processing product ' . $product->sku . ': ' . $e->getMessage());
                    Log::error('Error processing product with AI', [
                        'sku' => $product->sku,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->info('AI product updates process completed.');
            Log::info('Completed scheduled AI product updates');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error in AI product updates process: ' . $e->getMessage());
            Log::error('Error in scheduled AI product updates: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
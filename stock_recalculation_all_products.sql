-- Stock recalculation for ALL products - matches system logic (ProductUtil::getVariationStockMisMatch)
-- Location-aware, uses stock_adjustment_lines, quantity_returned, production

SELECT 
    p.id AS product_id,
    p.name AS product_name,
    p.sku AS product_sku,
    v.id AS variation_id,
    v.sub_sku AS variation_sku,
    vld.location_id,
    vld.qty_available AS current_stock,
    
    -- Total IN
    (
        SELECT COALESCE(SUM(pl.quantity), 0) FROM purchase_lines pl
        JOIN transactions t ON t.id = pl.transaction_id
        WHERE t.status = 'received' AND t.type = 'purchase'
        AND t.location_id = vld.location_id AND pl.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(pl.quantity), 0) FROM purchase_lines pl
        JOIN transactions t ON t.id = pl.transaction_id
        WHERE t.type = 'opening_stock' AND t.status = 'received'
        AND t.location_id = vld.location_id AND pl.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(pl.quantity), 0) FROM purchase_lines pl
        JOIN transactions t ON t.id = pl.transaction_id
        WHERE t.status = 'received' AND t.type = 'purchase_transfer'
        AND t.location_id = vld.location_id AND pl.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(tsl.quantity_returned), 0) FROM transaction_sell_lines tsl
        JOIN transactions t ON t.id = tsl.transaction_id
        WHERE t.status IN ('final', 'under processing') AND t.type = 'sell'
        AND t.location_id = vld.location_id AND tsl.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(pl.quantity), 0) FROM purchase_lines pl
        JOIN transactions t ON t.id = pl.transaction_id
        WHERE t.status = 'received' AND t.type = 'production_purchase'
        AND t.location_id = vld.location_id AND pl.variation_id = v.id
    ) AS total_in,
    
    -- Total OUT
    (
        SELECT COALESCE(SUM(tsl.quantity), 0) FROM transaction_sell_lines tsl
        JOIN transactions t ON t.id = tsl.transaction_id
        WHERE t.status IN ('final', 'under processing') AND t.type = 'sell'
        AND t.location_id = vld.location_id AND tsl.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(tsl.quantity), 0) FROM transaction_sell_lines tsl
        JOIN transactions t ON t.id = tsl.transaction_id
        WHERE t.status IN ('final', 'under processing') AND t.type = 'sell_transfer'
        AND t.location_id = vld.location_id AND tsl.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(sal.quantity), 0) FROM stock_adjustment_lines sal
        JOIN transactions t ON t.id = sal.transaction_id
        WHERE t.type = 'stock_adjustment'
        AND t.location_id = vld.location_id AND sal.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(pl.quantity_returned), 0) FROM purchase_lines pl
        JOIN transactions t ON t.id = pl.transaction_id
        WHERE t.status = 'received' AND t.type = 'purchase'
        AND t.location_id = vld.location_id AND pl.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(pl.quantity_returned), 0) FROM purchase_lines pl
        JOIN transactions t ON t.id = pl.transaction_id
        WHERE t.type = 'purchase_return'
        AND t.location_id = vld.location_id AND pl.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(tsl.quantity), 0) FROM transaction_sell_lines tsl
        JOIN transactions t ON t.id = tsl.transaction_id
        WHERE t.status IN ('final', 'under processing') AND t.type = 'production_sell'
        AND t.location_id = vld.location_id AND tsl.variation_id = v.id
    ) AS total_out,
    
    -- Real Stock (IN - OUT)
    (
        SELECT COALESCE(SUM(pl.quantity), 0) FROM purchase_lines pl
        JOIN transactions t ON t.id = pl.transaction_id
        WHERE t.status = 'received' AND t.type = 'purchase'
        AND t.location_id = vld.location_id AND pl.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(pl.quantity), 0) FROM purchase_lines pl
        JOIN transactions t ON t.id = pl.transaction_id
        WHERE t.type = 'opening_stock' AND t.status = 'received'
        AND t.location_id = vld.location_id AND pl.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(pl.quantity), 0) FROM purchase_lines pl
        JOIN transactions t ON t.id = pl.transaction_id
        WHERE t.status = 'received' AND t.type = 'purchase_transfer'
        AND t.location_id = vld.location_id AND pl.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(tsl.quantity_returned), 0) FROM transaction_sell_lines tsl
        JOIN transactions t ON t.id = tsl.transaction_id
        WHERE t.status IN ('final', 'under processing') AND t.type = 'sell'
        AND t.location_id = vld.location_id AND tsl.variation_id = v.id
    ) + (
        SELECT COALESCE(SUM(pl.quantity), 0) FROM purchase_lines pl
        JOIN transactions t ON t.id = pl.transaction_id
        WHERE t.status = 'received' AND t.type = 'production_purchase'
        AND t.location_id = vld.location_id AND pl.variation_id = v.id
    ) - (
        SELECT COALESCE(SUM(tsl.quantity), 0) FROM transaction_sell_lines tsl
        JOIN transactions t ON t.id = tsl.transaction_id
        WHERE t.status IN ('final', 'under processing') AND t.type = 'sell'
        AND t.location_id = vld.location_id AND tsl.variation_id = v.id
    ) - (
        SELECT COALESCE(SUM(tsl.quantity), 0) FROM transaction_sell_lines tsl
        JOIN transactions t ON t.id = tsl.transaction_id
        WHERE t.status IN ('final', 'under processing') AND t.type = 'sell_transfer'
        AND t.location_id = vld.location_id AND tsl.variation_id = v.id
    ) - (
        SELECT COALESCE(SUM(sal.quantity), 0) FROM stock_adjustment_lines sal
        JOIN transactions t ON t.id = sal.transaction_id
        WHERE t.type = 'stock_adjustment'
        AND t.location_id = vld.location_id AND sal.variation_id = v.id
    ) - (
        SELECT COALESCE(SUM(pl.quantity_returned), 0) FROM purchase_lines pl
        JOIN transactions t ON t.id = pl.transaction_id
        WHERE t.status = 'received' AND t.type = 'purchase'
        AND t.location_id = vld.location_id AND pl.variation_id = v.id
    ) - (
        SELECT COALESCE(SUM(pl.quantity_returned), 0) FROM purchase_lines pl
        JOIN transactions t ON t.id = pl.transaction_id
        WHERE t.type = 'purchase_return'
        AND t.location_id = vld.location_id AND pl.variation_id = v.id
    ) - (
        SELECT COALESCE(SUM(tsl.quantity), 0) FROM transaction_sell_lines tsl
        JOIN transactions t ON t.id = tsl.transaction_id
        WHERE t.status IN ('final', 'under processing') AND t.type = 'production_sell'
        AND t.location_id = vld.location_id AND tsl.variation_id = v.id
    ) AS real_stock

FROM products p
JOIN variations v ON v.product_id = p.id
JOIN variation_location_details vld ON vld.variation_id = v.id
WHERE p.business_id = 1
  AND p.enable_stock = 1

HAVING ABS(current_stock - real_stock) > 0.001
ORDER BY ABS(current_stock - real_stock) DESC
LIMIT 100;

-- Summary: count of mismatched products
SELECT 
    COUNT(*) AS total_with_mismatches,
    SUM(ABS(current_stock - real_stock)) AS total_abs_discrepancy
FROM (
    SELECT 
        vld.qty_available AS current_stock,
        (
            SELECT COALESCE(SUM(pl.quantity), 0) FROM purchase_lines pl
            JOIN transactions t ON t.id = pl.transaction_id
            WHERE t.status = 'received' AND t.type = 'purchase'
            AND t.location_id = vld.location_id AND pl.variation_id = v.id
        ) + (
            SELECT COALESCE(SUM(pl.quantity), 0) FROM purchase_lines pl
            JOIN transactions t ON t.id = pl.transaction_id
            WHERE t.type = 'opening_stock' AND t.status = 'received'
            AND t.location_id = vld.location_id AND pl.variation_id = v.id
        ) + (
            SELECT COALESCE(SUM(pl.quantity), 0) FROM purchase_lines pl
            JOIN transactions t ON t.id = pl.transaction_id
            WHERE t.status = 'received' AND t.type = 'purchase_transfer'
            AND t.location_id = vld.location_id AND pl.variation_id = v.id
        ) + (
            SELECT COALESCE(SUM(tsl.quantity_returned), 0) FROM transaction_sell_lines tsl
            JOIN transactions t ON t.id = tsl.transaction_id
            WHERE t.status IN ('final', 'under processing') AND t.type = 'sell'
            AND t.location_id = vld.location_id AND tsl.variation_id = v.id
        ) + (
            SELECT COALESCE(SUM(pl.quantity), 0) FROM purchase_lines pl
            JOIN transactions t ON t.id = pl.transaction_id
            WHERE t.status = 'received' AND t.type = 'production_purchase'
            AND t.location_id = vld.location_id AND pl.variation_id = v.id
        ) - (
            SELECT COALESCE(SUM(tsl.quantity), 0) FROM transaction_sell_lines tsl
            JOIN transactions t ON t.id = tsl.transaction_id
            WHERE t.status IN ('final', 'under processing') AND t.type = 'sell'
            AND t.location_id = vld.location_id AND tsl.variation_id = v.id
        ) - (
            SELECT COALESCE(SUM(tsl.quantity), 0) FROM transaction_sell_lines tsl
            JOIN transactions t ON t.id = tsl.transaction_id
            WHERE t.status IN ('final', 'under processing') AND t.type = 'sell_transfer'
            AND t.location_id = vld.location_id AND tsl.variation_id = v.id
        ) - (
            SELECT COALESCE(SUM(sal.quantity), 0) FROM stock_adjustment_lines sal
            JOIN transactions t ON t.id = sal.transaction_id
            WHERE t.type = 'stock_adjustment'
            AND t.location_id = vld.location_id AND sal.variation_id = v.id
        ) - (
            SELECT COALESCE(SUM(pl.quantity_returned), 0) FROM purchase_lines pl
            JOIN transactions t ON t.id = pl.transaction_id
            WHERE t.status = 'received' AND t.type = 'purchase'
            AND t.location_id = vld.location_id AND pl.variation_id = v.id
        ) - (
            SELECT COALESCE(SUM(pl.quantity_returned), 0) FROM purchase_lines pl
            JOIN transactions t ON t.id = pl.transaction_id
            WHERE t.type = 'purchase_return'
            AND t.location_id = vld.location_id AND pl.variation_id = v.id
        ) - (
            SELECT COALESCE(SUM(tsl.quantity), 0) FROM transaction_sell_lines tsl
            JOIN transactions t ON t.id = tsl.transaction_id
            WHERE t.status IN ('final', 'under processing') AND t.type = 'production_sell'
            AND t.location_id = vld.location_id AND tsl.variation_id = v.id
        ) AS real_stock
    FROM products p
    JOIN variations v ON v.product_id = p.id
    JOIN variation_location_details vld ON vld.variation_id = v.id
    WHERE p.business_id = 1
      AND p.enable_stock = 1
    HAVING ABS(current_stock - real_stock) > 0.001
) AS mismatches;

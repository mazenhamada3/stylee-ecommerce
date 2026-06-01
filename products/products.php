<?php
// ─────────────────────────────────────────────
//  products/products.php
//  Fetch, create, delete products + demo reset
// ─────────────────────────────────────────────

/**
 * Fetch one product (by $id) or all products.
 */
function fetch_products(?string $id = null): array
{
    $pdo = db();

    if ($id) {
        $stmt = $pdo->prepare('SELECT id, name, category, gender, price, description FROM products WHERE id = ? ORDER BY created_at DESC');
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->query('SELECT id, name, category, gender, price, description FROM products ORDER BY created_at DESC, name ASC');
    }

    $rows = $stmt->fetchAll();
    if (!$rows) return [];

    $products = [];
    $productIds = [];

    foreach ($rows as $row) {
        $row['price'] = (float) $row['price'];
        $row['colors'] = [];
        $row['sizes'] = [];
        $products[$row['id']] = $row;
        $productIds[] = $row['id'];
    }

    // ── Load colors ─────────────────────────────────────
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $colorStmt = $pdo->prepare("
        SELECT id, product_id, name, hex, photo
        FROM product_colors
        WHERE product_id IN ($placeholders)
        ORDER BY sort_order ASC, id ASC
    ");
    $colorStmt->execute($productIds);

    $colorsById = [];

    foreach ($colorStmt->fetchAll() as $color) {
        $colorData = [
            'id' => (int)$color['id'],
            'name' => $color['name'],
            'hex' => $color['hex'],
            'photo' => $color['photo'],
            'sizes' => []
        ];

        $products[$color['product_id']]['colors'][] = $colorData;
        $colorsById[(int)$color['id']] = [
            $color['product_id'],
            count($products[$color['product_id']]['colors']) - 1
        ];
    }

    // ── Load sizes per color (FIXED SORTING HERE) ───────
    if ($colorsById) {
        $colorIds = array_keys($colorsById);
        $placeholders = implode(',', array_fill(0, count($colorIds), '?'));

        $sizeStmt = $pdo->prepare("
            SELECT color_id, size_name, qty_stock
            FROM product_color_sizes
            WHERE color_id IN ($placeholders)
            ORDER BY FIELD(size_name, 'S','M','L','XL','XXL')
        ");

        $sizeStmt->execute($colorIds);

        foreach ($sizeStmt->fetchAll() as $size) {
            [$productId, $colorIndex] = $colorsById[(int)$size['color_id']];

            $products[$productId]['colors'][$colorIndex]['sizes'][] = [
                'name' => $size['size_name'],
                'qty'  => (int)$size['qty_stock']
            ];
        }
    }

    // ── Aggregate sizes ────────────────────────────────
    foreach ($products as &$product) {
        $agg = [];

        foreach ($product['colors'] as $color) {
            foreach ($color['sizes'] as $size) {
                $agg[$size['name']] = ($agg[$size['name']] ?? 0) + $size['qty'];
            }
        }

        $product['sizes'] = [];
        foreach (['S','M','L','XL','XXL'] as $s) {
            if (isset($agg[$s])) {
                $product['sizes'][] = [
                    'name' => $s,
                    'qty' => $agg[$s]
                ];
            }
        }
    }
    unset($product);

    return array_values($products);
}
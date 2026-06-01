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
    if (!$rows) {
        return [];
    }

    $products   = [];
    $productIds = [];

    foreach ($rows as $row) {
        $row['price']  = (float) $row['price'];
        $row['colors'] = [];
        $row['sizes']  = []; // Compatibility only. Frontend uses color-level sizes.
        $products[$row['id']] = $row;
        $productIds[]         = $row['id'];
    }

    // ── Load colors ───────────────────────────────────────────────────────────
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $colorStmt    = $pdo->prepare("SELECT id, product_id, name, hex, photo FROM product_colors WHERE product_id IN ($placeholders) ORDER BY sort_order ASC, id ASC");
    $colorStmt->execute($productIds);

    $colorsById = [];
    foreach ($colorStmt->fetchAll() as $color) {
        $colorData = [
            'id'    => (int) $color['id'],
            'name'  => $color['name'],
            'hex'   => $color['hex'],
            'photo' => $color['photo'],
            'sizes' => [],
        ];
        $products[$color['product_id']]['colors'][] = $colorData;
        $colorIndex = count($products[$color['product_id']]['colors']) - 1;
        $colorsById[(int) $color['id']] = [$color['product_id'], $colorIndex];
    }

    // ── Load sizes per color ──────────────────────────────────────────────────
    if ($colorsById) {
        $colorIds          = array_keys($colorsById);
        $colorPlaceholders = implode(',', array_fill(0, count($colorIds), '?'));
        $sizeStmt          = $pdo->prepare("SELECT color_id, size_name, qty_stock FROM product_color_sizes WHERE color_id IN ($colorPlaceholders) ORDER BY FIELD(size_name, 'S', 'M', 'L', 'XL', 'XXL'), size_name ASC");
        $sizeStmt->execute($colorIds);

        foreach ($sizeStmt->fetchAll() as $size) {
            [$productId, $colorIndex] = $colorsById[(int) $size['color_id']];
            $products[$productId]['colors'][$colorIndex]['sizes'][] = [
                'name' => $size['size_name'],
                'qty'  => (int) $size['qty_stock'],
            ];
        }
    }

    // ── Aggregate sizes for top-level compatibility ───────────────────────────
    foreach ($products as &$product) {
        $aggregate = [];
        foreach ($product['colors'] as $color) {
            foreach ($color['sizes'] as $size) {
                $name = $size['name'];
                $aggregate[$name] = ($aggregate[$name] ?? 0) + (int) $size['qty'];
            }
        }
        $product['sizes'] = array_map(
            fn($name, $qty) => ['name' => $name, 'qty' => $qty],
            array_keys($aggregate),
            array_values($aggregate)
        );
    }
    unset($product);

    return array_values($products);
}

/**
 * Create a new product (admin only).
 */
function create_product(array $data): void
{
    $name        = trim((string)($data['name']        ?? ''));
    $category    = trim((string)($data['category']    ?? ''));
    $gender      = trim((string)($data['gender']      ?? 'both'));
    $price       = (float)($data['price']             ?? 0);
    $description = trim((string)($data['description'] ?? ''));
    $colors      = $data['colors'] ?? [];

    if ($name === '' || $category === '' || $price <= 0 || $description === '') {
        json_response(['success' => false, 'message' => 'Fill all product fields.'], 422);
    }

    if (!in_array($gender, ['men', 'women', 'both'], true)) {
        $gender = 'both';
    }

    if (!is_array($colors) || count($colors) === 0) {
        json_response(['success' => false, 'message' => 'Add at least one product color.'], 422);
    }

    $validColors = [];
    foreach ($colors as $color) {
        $colorName = trim((string)($color['name']  ?? ''));
        $hex       = trim((string)($color['hex']   ?? '#111111'));
        $photo     = trim((string)($color['photo'] ?? ''));
        $sizes     = $color['sizes'] ?? [];

        if ($colorName === '' || $photo === '') continue;

        $validSizes = [];
        foreach ($sizes as $size) {
            $sizeName = trim((string)($size['name'] ?? ''));
            $qty      = max(0, (int)($size['qty']   ?? 0));
            if ($sizeName !== '') {
                $validSizes[] = ['name' => $sizeName, 'qty' => $qty];
            }
        }

        if (!array_filter($validSizes, fn($s) => (int)$s['qty'] > 0)) continue;

        $validColors[] = ['name' => $colorName, 'hex' => $hex, 'photo' => $photo, 'sizes' => $validSizes];
    }

    if (!$validColors) {
        json_response(['success' => false, 'message' => 'Each product needs at least one color with stock for at least one size.'], 422);
    }

    $productId = make_product_id($name);
    $pdo       = db();
    $pdo->beginTransaction();

    try {
        $pdo->prepare('INSERT INTO products (id, name, category, gender, price, description) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$productId, $name, $category, $gender, $price, $description]);

        $colorStmt = $pdo->prepare('INSERT INTO product_colors (product_id, name, hex, photo, sort_order) VALUES (?, ?, ?, ?, ?)');
        $sizeStmt  = $pdo->prepare('INSERT INTO product_color_sizes (color_id, size_name, qty_stock) VALUES (?, ?, ?)');

        foreach ($validColors as $index => $color) {
            $colorStmt->execute([$productId, $color['name'], $color['hex'], $color['photo'], $index]);
            $colorId = (int) $pdo->lastInsertId();
            foreach ($color['sizes'] as $size) {
                $sizeStmt->execute([$colorId, $size['name'], $size['qty']]);
            }
        }

        $pdo->commit();
        json_response(['success' => true, 'message' => 'Product added.', 'product' => fetch_products($productId)[0] ?? null], 201);
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Delete a product by ID (admin only).
 */
function delete_product(string $id): void
{
    $id = trim($id);
    if ($id === '') {
        json_response(['success' => false, 'message' => 'Product id is required.'], 422);
    }

    db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    json_response(['success' => true, 'message' => 'Product deleted.']);
}

/**
 * Wipe all data and re-seed demo products (admin only).
 */
function reset_demo_products(): void
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $pdo->exec('DELETE FROM order_items');
        $pdo->exec('DELETE FROM orders');
        $pdo->exec('DELETE FROM product_color_sizes');
        $pdo->exec('DELETE FROM product_colors');
        $pdo->exec('DELETE FROM products');

        seed_product('puffer-jacket', 'Premium Puffer Jacket', 'Jackets', 'men', 179,
            'Oversized premium puffer with quilted paneling. Water-resistant shell and streetwear fit.',
            [
                ['Black', '#000000', 'https://images.unsplash.com/photo-1611312449408-fcece27cdbb7?w=900', [['S',8],['M',10],['L',7],['XL',5],['XXL',3]]],
                ['Navy',  '#263949', 'https://images.unsplash.com/photo-1523398002811-999ca8dec234?w=900', [['S',3],['M',6], ['L',4],['XL',2],['XXL',1]]],
                ['Brown', '#71430f', 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900', [['S',5],['M',4], ['L',3],['XL',0],['XXL',0]]],
            ]
        );

        seed_product('bomber-camo-set', 'Bomber & Camo Set', 'Sets', 'women', 189,
            'Relaxed streetwear set with oversized bomber jacket and camo pants.',
            [
                ['Olive', '#556b2f', 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900', [['S',6],['M',8],['L',5],['XL',4],['XXL',2]]],
                ['Black', '#111111', 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?w=900', [['S',2],['M',5],['L',2],['XL',1],['XXL',0]]],
            ]
        );

        seed_product('urban-black-ensemble', 'Urban Black Ensemble', 'Outerwear', 'both', 249,
            'Layered urban outfit made for daily streetwear styling.',
            [
                ['Black', '#000000', 'https://images.unsplash.com/photo-1523398002811-999ca8dec234?w=900', [['S',4],['M',6],['L',5],['XL',3],['XXL',1]]],
                ['Grey',  '#777777', 'https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?w=900', [['S',1],['M',2],['L',3],['XL',0],['XXL',0]]],
            ]
        );

        $pdo->commit();
        json_response(['success' => true, 'message' => 'Demo data reset.', 'products' => fetch_products()]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Insert one product + colors + sizes (used by reset_demo_products).
 */
function seed_product(string $id, string $name, string $category, string $gender, float $price, string $description, array $colors): void
{
    $pdo = db();
    $pdo->prepare('INSERT INTO products (id, name, category, gender, price, description) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$id, $name, $category, $gender, $price, $description]);

    $colorStmt = $pdo->prepare('INSERT INTO product_colors (product_id, name, hex, photo, sort_order) VALUES (?, ?, ?, ?, ?)');
    $sizeStmt  = $pdo->prepare('INSERT INTO product_color_sizes (color_id, size_name, qty_stock) VALUES (?, ?, ?)');

    foreach ($colors as $index => $color) {
        $colorStmt->execute([$id, $color[0], $color[1], $color[2], $index]);
        $colorId = (int) $pdo->lastInsertId();
        foreach ($color[3] as $size) {
            $sizeStmt->execute([$colorId, $size[0], $size[1]]);
        }
    }
}

/**
 * Turn a product name into a URL-safe slug with a random suffix.
 */
function make_product_id(string $name): string
{
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
    if ($slug === '') $slug = 'product';
    return $slug . '-' . bin2hex(random_bytes(3));
}
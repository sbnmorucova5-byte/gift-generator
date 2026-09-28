<?php
require_once 'db.php';

$message = '';

// 1. SILMEK (DELETE) EMELLIYYATI
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $del_sql = "DELETE FROM gifts WHERE id = ?";
    $stmt = $conn->prepare($del_sql);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $message = "Hədiyyə uğurla silindi!";
    }
}

// 2. YENİ ƏLAVƏ ETMEK (CREATE) VE YA REDAKTƏ ETMEK (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $min_price = floatval($_POST['min_price'] ?? 0);
    $max_price = floatval($_POST['max_price'] ?? 0);
    $min_age = intval($_POST['min_age'] ?? 1);
    $max_age = intval($_POST['max_age'] ?? 100);
    $category_id = intval($_POST['category_id'] ?? 0);
    $interest_id = intval($_POST['interest_id'] ?? 0);
    $edit_id = intval($_POST['edit_id'] ?? 0);

    if ($edit_id > 0) {
        // UPDATE (Redaktə et)
        $upd_sql = "UPDATE gifts SET title=?, min_price=?, max_price=?, min_age=?, max_age=?, category_id=?, interest_id=? WHERE id=?";
        $stmt = $conn->prepare($upd_sql);
        $stmt->bind_param("sddddiii", $title, $min_price, $max_price, $min_age, $max_age, $category_id, $interest_id, $edit_id);
        if ($stmt->execute()) {
            $message = "Hədiyyə məlumatları yeniləndi!";
        }
    } else {
        // CREATE (Yeni Əlavə et)
        $ins_sql = "INSERT INTO gifts (title, min_price, max_price, min_age, max_age, category_id, interest_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($ins_sql);
        $stmt->bind_param("sddddii", $title, $min_price, $max_price, $min_age, $max_age, $category_id, $interest_id);
        if ($stmt->execute()) {
            $message = "Yeni hədiyyə uğurla əlavə olundu!";
        }
    }
}

// REDAKTƏ OLUNACAQ HƏDİYYƏNİ TAPMAQ
$edit_gift = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $e_sql = "SELECT * FROM gifts WHERE id = ?";
    $stmt = $conn->prepare($e_sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_gift = $stmt->get_result()->fetch_assoc();
}

// KATEQORİYA VƏ MARAQLARI BAZADAN ÇƏKMƏK
$categories = $conn->query("SELECT * FROM categories");
$interests = $conn->query("SELECT * FROM interests");

// BÜTÜN HƏDİYYƏLƏRİ SİYAHILAMAQ (READ)
$gifts_list = $conn->query("
    SELECT g.*, c.name as category_name, c.icon as category_icon, i.name as interest_name, i.icon as interest_icon 
    FROM gifts g
    LEFT JOIN categories c ON g.category_id = c.id
    LEFT JOIN interests i ON g.interest_id = i.id
    ORDER BY g.id DESC
");
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Gift Generator</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="glass-container admin-container">
        
        <header class="header">
            <div class="badge">DATABASE MANAGEMENT</div>
            <h1>🛠️ Admin Dashboard (CRUD)</h1>
            <p><a href="index.php" style="color: #00d4ff; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Əsas Səhifəyə Qayıt</a></p>
        </header>

        <?php if ($message): ?>
            <div class="alert-msg"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- 1. HƏDİYYƏ ƏLAVƏ / REDAKTƏ FORMASI -->
        <div class="admin-card">
            <h3><?php echo $edit_gift ? '✏️ Hədiyyəni Redaktə Et' : '➕ Yeni Hədiyyə Əlavə Et'; ?></h3>
            <form action="admin.php" method="POST" class="admin-form">
                
                <input type="hidden" name="edit_id" value="<?php echo $edit_gift['id'] ?? 0; ?>">

                <div class="form-group">
                    <label>Hədiyyə Adı (Title)</label>
                    <input type="text" name="title" required class="input-control" value="<?php echo htmlspecialchars($edit_gift['title'] ?? ''); ?>" placeholder="Məs: Gaming Headset">
                </div>

                <div class="form-row">
                    <div class="form-group half-width">
                        <label>Min Qiymət (AZN)</label>
                        <input type="number" step="0.01" name="min_price" required class="input-control" value="<?php echo $edit_gift['min_price'] ?? ''; ?>">
                    </div>
                    <div class="form-group half-width">
                        <label>Max Qiymət (AZN)</label>
                        <input type="number" step="0.01" name="max_price" required class="input-control" value="<?php echo $edit_gift['max_price'] ?? ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group half-width">
                        <label>Min Yaş</label>
                        <input type="number" name="min_age" required class="input-control" value="<?php echo $edit_gift['min_age'] ?? 1; ?>">
                    </div>
                    <div class="form-group half-width">
                        <label>Max Yaş</label>
                        <input type="number" name="max_age" required class="input-control" value="<?php echo $edit_gift['max_age'] ?? 100; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group half-width">
                        <label>Kimə (Category)</label>
                        <select name="category_id" class="input-control" required>
                            <?php 
                            $categories->data_seek(0);
                            while($cat = $categories->fetch_assoc()): 
                                $selected = ($edit_gift && $edit_gift['category_id'] == $cat['id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $selected; ?>>
                                    <?php echo $cat['icon'] . ' ' . ucfirst($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group half-width">
                        <label>Maraq (Interest)</label>
                        <select name="interest_id" class="input-control" required>
                            <?php 
                            $interests->data_seek(0);
                            while($inst = $interests->fetch_assoc()): 
                                $selected = ($edit_gift && $edit_gift['interest_id'] == $inst['id']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo $inst['id']; ?>" <?php echo $selected; ?>>
                                    <?php echo $inst['icon'] . ' ' . ucfirst($inst['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <?php echo $edit_gift ? '💾 YENİLƏ' : '➕ BAZAYA ƏLAVƏ ET'; ?>
                </button>
            </form>
        </div>

        <!-- 2. BAZADAKİ HƏDİYYƏLƏRİN SİYAHISI (TABLE) -->
        <div class="admin-card" style="margin-top: 30px;">
            <h3>📊 Bazadakı Hədiyyələr Siyahısı</h3>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hədiyyə Adı</th>
                            <th>Qiymət</th>
                            <th>Yaş</th>
                            <th>Kimə</th>
                            <th>Maraq</th>
                            <th>Əməliyyatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $gifts_list->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                                <td><?php echo $row['min_price'] . ' - ' . $row['max_price']; ?> AZN</td>
                                <td><?php echo $row['min_age'] . ' - ' . $row['max_age']; ?></td>
                                <td><?php echo $row['category_icon'] . ' ' . ucfirst($row['category_name']); ?></td>
                                <td><?php echo $row['interest_icon'] . ' ' . ucfirst($row['interest_name']); ?></td>
                                <td>
                                    <a href="admin.php?edit=<?php echo $row['id']; ?>" class="action-btn edit-btn"><i class="fa-solid fa-pen"></i> Redaktə</a>
                                    <a href="admin.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Silməyə əminsiniz?')" class="action-btn delete-btn"><i class="fa-solid fa-trash"></i> Sil</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>
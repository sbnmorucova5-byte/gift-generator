<?php
require_once 'db.php';

$suggested_gifts = [];
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    
    $person = $_POST['person'] ?? '';
    $age = intval($_POST['age'] ?? 0);
    $min_budget = floatval($_POST['min_budget'] ?? 0);
    $max_budget = floatval($_POST['max_budget'] ?? 0);
    $interest = $_POST['interest'] ?? 'any';

    if ($min_budget == 0 && $max_budget == 0) {
        $sql = "SELECT * FROM gifts WHERE min_price = 0 AND max_price = 0";
        $result = $conn->query($sql);
    } else {
        $sql = "SELECT g.* FROM gifts g
                LEFT JOIN categories c ON g.category_id = c.id
                LEFT JOIN interests i ON g.interest_id = i.id
                WHERE (g.min_price <= ? AND g.max_price >= ?)
                   OR (g.min_price = 0 AND g.max_price = 0)";

        if (!empty($person) && $person !== 'other') {
            $sql .= " AND (c.name = ? OR g.category_id IS NULL)";
        }
        
        if (!empty($interest) && $interest !== 'any') {
            $sql .= " AND (i.name = ? OR g.interest_id IS NULL)";
        }

        $stmt = $conn->prepare($sql);
        
        if (!empty($person) && $person !== 'other' && !empty($interest) && $interest !== 'any') {
            $stmt->bind_param("ddss", $max_budget, $min_budget, $person, $interest);
        } elseif (!empty($person) && $person !== 'other') {
            $stmt->bind_param("dds", $max_budget, $min_budget, $person);
        } elseif (!empty($interest) && $interest !== 'any') {
            $stmt->bind_param("dds", $max_budget, $min_budget, $interest);
        } else {
            $stmt->bind_param("dd", $max_budget, $min_budget);
        }

        $stmt->execute();
        $result = $stmt->get_result();
    }

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $suggested_gifts[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gift Generator - Cyber Edition</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="glass-container">
        
        <header class="header">
            <div class="badge">SMART GIFT FINDER</div>
            <h1>🎁 Gift Generator</h1>
            <p>Find the perfect gift suggestions tailored for your loved ones</p>
        </header>

        <!-- ƏSAS FORMA -->
        <form action="index.php" method="POST" class="gift-form">
            
            <div class="form-group">
                <label class="section-title"><i class="fa-solid fa-user-tag"></i> 1. Who is the person?</label>
                <div class="options-grid">
                    <label class="card-option"><input type="radio" name="person" value="mother" required><div class="card-inner"><span class="emoji">👩</span><span class="text">Mother</span></div></label>
                    <label class="card-option"><input type="radio" name="person" value="father"><div class="card-inner"><span class="emoji">👨</span><span class="text">Father</span></div></label>
                    <label class="card-option"><input type="radio" name="person" value="sister"><div class="card-inner"><span class="emoji">👧</span><span class="text">Sister</span></div></label>
                    <label class="card-option"><input type="radio" name="person" value="brother"><div class="card-inner"><span class="emoji">👦</span><span class="text">Brother</span></div></label>
                    <label class="card-option"><input type="radio" name="person" value="friend"><div class="card-inner"><span class="emoji">👭</span><span class="text">Friend</span></div></label>
                    <label class="card-option"><input type="radio" name="person" value="partner"><div class="card-inner"><span class="emoji">❤️</span><span class="text">Partner</span></div></label>
                    <label class="card-option"><input type="radio" name="person" value="other"><div class="card-inner"><span class="emoji">🎁</span><span class="text">Other</span></div></label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group half-width">
                    <label class="section-title" for="age"><i class="fa-solid fa-cake-candles"></i> 2. Age?</label>
                    <input type="number" id="age" name="age" placeholder="e.g. 22" min="1" max="100" required class="input-control">
                </div>

                <div class="form-group half-width">
                    <label class="section-title"><i class="fa-solid fa-wallet"></i> 3. Budget Range (AZN)?</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" name="min_budget" placeholder="Min (0 for DIY)" min="0" required class="input-control">
                        <input type="number" name="max_budget" placeholder="Max (e.g. 30)" min="0" required class="input-control">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="section-title"><i class="fa-solid fa-heart"></i> 4. Interest?</label>
                <div class="options-grid">
                    <label class="card-option"><input type="radio" name="interest" value="art" required><div class="card-inner"><span class="emoji">🎨</span><span class="text">Art</span></div></label>
                    <label class="card-option"><input type="radio" name="interest" value="books"><div class="card-inner"><span class="emoji">📚</span><span class="text">Books</span></div></label>
                    <label class="card-option"><input type="radio" name="interest" value="games"><div class="card-inner"><span class="emoji">🎮</span><span class="text">Games</span></div></label>
                    <label class="card-option"><input type="radio" name="interest" value="music"><div class="card-inner"><span class="emoji">🎵</span><span class="text">Music</span></div></label>
                    <label class="card-option"><input type="radio" name="interest" value="sport"><div class="card-inner"><span class="emoji">⚽</span><span class="text">Sport</span></div></label>
                    <label class="card-option"><input type="radio" name="interest" value="any"><div class="card-inner"><span class="emoji">❓</span><span class="text">I don't have any idea</span></div></label>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>🎁 FIND GIFT SUGGESTIONS</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>

        </form>

        <!-- NƏTİCƏLƏR -->
        <?php if ($searched): ?>
            <div class="result-box">
                <h2>🎉 Found Gift Recommendations (<?php echo count($suggested_gifts); ?>)</h2>
                
                <?php if (!empty($suggested_gifts)): ?>
                    <div class="gifts-grid">
                        <?php foreach ($suggested_gifts as $gift): ?>
                            <div class="gift-card">
                                <div class="gift-img-wrapper">
                                    <img src="<?php echo htmlspecialchars($gift['image_url']); ?>" 
                                         alt="Gift Image" 
                                         onerror="this.onerror=null; this.src='https://via.placeholder.com/400x250/0f172a/00d4ff?text=Gift+Idea';">
                                </div>
                                <div class="gift-info">
                                    <h3><?php echo htmlspecialchars($gift['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($gift['description'] ?? ''); ?></p>
                                    <div class="gift-price">
                                        <?php if($gift['min_price'] == 0 && $gift['max_price'] == 0): ?>
                                            <span class="free-badge">✨ 0 AZN (Handmade DIY)</span>
                                        <?php else: ?>
                                            💰 <?php echo $gift['min_price'] . ' - ' . $gift['max_price']; ?> AZN
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="result-card no-match">
                        <h2>🙁 No matching gifts found</h2>
                        <p>Try widening your budget range or select "I don't have any idea" under interests.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
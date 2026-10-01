<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_auth();
$user = get_auth_user();
$userId = (int)$user['id'];
$pdo = get_db();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $stmt = $pdo->prepare("
        UPDATE profiles
        SET first_name = :fn, last_name = :ln, phone = :ph, country = :ct, city = :ci, address = :ad, updated_at = NOW()
        WHERE user_id = :uid
    ");
    $stmt->execute([
        ':fn'  => $firstName,
        ':ln'  => $lastName,
        ':ph'  => $phone,
        ':ct'  => $country,
        ':ci'  => $city,
        ':ad'  => $address,
        ':uid' => $userId
    ]);

    set_flash('success', 'Profile information updated successfully.');
    redirect('/user/profile.php');
}

// Fetch fresh profile
$pStmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id = :uid LIMIT 1");
$pStmt->execute([':uid' => $userId]);
$profile = $pStmt->fetch() ?: [];

$pageTitle = 'Player Profile & Verification — Apex Gaming Platform';
$activeNav = 'profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-white">Player Profile</h1>
            <p class="text-xs text-slate-400 mt-1">Manage personal identification and account details</p>
        </div>
        <div>
            <span class="text-xs text-slate-400 mr-2">KYC Status:</span>
            <?= render_status_badge($profile['kyc_status'] ?? 'unverified') ?>
        </div>
    </div>

    <div class="glass-card p-6 sm:p-8 rounded-3xl space-y-6">
        
        <!-- Account Info Header -->
        <div class="flex items-center gap-4 pb-6 border-b border-white/[0.06]">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-600 flex items-center justify-center text-white font-bold text-lg uppercase shadow-lg shadow-brand-600/30">
                <?= substr($user['username'], 0, 2) ?>
            </div>
            <div>
                <div class="text-base font-bold text-white"><?= e($user['username']) ?></div>
                <div class="text-xs text-slate-400 font-mono"><?= e($user['email']) ?></div>
                <div class="text-[11px] text-brand-400 mt-0.5">Referral Code: <span class="font-mono font-bold"><?= e($user['referral_code']) ?></span></div>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="/user/profile.php" class="space-y-4">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">First Name</label>
                    <input type="text" name="first_name" value="<?= e($profile['first_name'] ?? '') ?>"
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Last Name</label>
                    <input type="text" name="last_name" value="<?= e($profile['last_name'] ?? '') ?>"
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Phone Number</label>
                    <input type="text" name="phone" value="<?= e($profile['phone'] ?? '') ?>" placeholder="+1 234 567 8900"
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Country</label>
                    <input type="text" name="country" value="<?= e($profile['country'] ?? '') ?>" placeholder="e.g. United Kingdom"
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">City</label>
                    <input type="text" name="city" value="<?= e($profile['city'] ?? '') ?>"
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Residential Address</label>
                    <input type="text" name="address" value="<?= e($profile['address'] ?? '') ?>"
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-brand-600/30">
                Save Profile Changes
            </button>
        </form>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

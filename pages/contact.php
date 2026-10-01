<?php
$pageTitle = 'Contact Us — Apex Gaming Platform';
$activeNav = 'contact';
require_once __DIR__ . '/../includes/header.php';

$sent = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_abort();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Please fill out all contact form fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        // If logged in, create a support ticket automatically!
        if ($authUser) {
            create_support_ticket((int)$authUser['id'], "General Inquiry: {$name}", 'General Support', 'medium', $message);
        }
        $sent = true;
    }
}
?>

<div class="max-w-2xl mx-auto py-8">
    <div class="mb-8 text-center">
        <span class="text-xs font-bold text-brand-400 uppercase tracking-wider">Get in Touch</span>
        <h1 class="text-3xl font-black text-white mt-1">Contact Support</h1>
        <p class="text-slate-400 text-xs mt-1">Our technical and compliance team is available around the clock.</p>
    </div>

    <div class="glass-card p-6 sm:p-8 rounded-2xl">
        <?php if ($sent): ?>
            <div class="text-center py-8 space-y-4">
                <div class="w-12 h-12 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-2xl flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="text-base font-bold text-white">Message Received</h3>
                <p class="text-xs text-slate-400">Thank you for reaching out. A compliance specialist will reply to your provided email within 24 hours.</p>
                <?php if ($authUser): ?>
                    <a href="/user/support.php" class="inline-block mt-2 px-4 py-2 rounded-xl bg-brand-600 text-white text-xs font-semibold">View In Support Tickets</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/pages/contact.php" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Your Full Name</label>
                    <input type="text" name="name" value="<?= e($authUser['username'] ?? '') ?>" required
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Email Address</label>
                    <input type="email" name="email" value="<?= e($authUser['email'] ?? '') ?>" required
                           class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1">Message / Inquiry</label>
                    <textarea name="message" rows="4" required placeholder="Describe your question or issue in detail..."
                              class="w-full px-4 py-2.5 rounded-xl bg-dark-900 border border-white/[0.08] text-white text-xs focus:outline-none focus:border-brand-500"></textarea>
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-semibold text-xs transition-all shadow-lg shadow-brand-600/30">
                    Send Message
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

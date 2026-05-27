<?php
declare(strict_types=1);

require __DIR__ . '/auth.php';

$user = require_auth();
$flash = get_flash();
$contactErrors = [];
$contactForm = [
    'full_name' => $user['full_name'],
    'email' => $user['email'],
    'phone' => $user['phone'],
    'preferred_location' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['form_type'] ?? '') === 'viewing_request') {
    $contactForm['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $contactForm['email'] = trim((string) ($_POST['email'] ?? ''));
    $contactForm['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $contactForm['preferred_location'] = trim((string) ($_POST['preferred_location'] ?? ''));
    $contactForm['message'] = trim((string) ($_POST['message'] ?? ''));

    if ($contactForm['full_name'] === '' || mb_strlen($contactForm['full_name']) < 3) {
        $contactErrors[] = 'Enter your full name using at least 3 characters.';
    }

    if (!filter_var($contactForm['email'], FILTER_VALIDATE_EMAIL)) {
        $contactErrors[] = 'Enter a valid email address.';
    }

    if ($contactForm['phone'] === '' || mb_strlen($contactForm['phone']) < 10) {
        $contactErrors[] = 'Enter a valid phone number.';
    }

    if ($contactForm['preferred_location'] === '' || mb_strlen($contactForm['preferred_location']) < 3) {
        $contactErrors[] = 'Tell us the area or property you are interested in.';
    }

    if ($contactForm['message'] === '' || mb_strlen($contactForm['message']) < 12) {
        $contactErrors[] = 'Write a short message with at least 12 characters.';
    }

    if (!$contactErrors) {
        try {
            create_contact_request(
                (int) $user['id'],
                $contactForm['full_name'],
                $contactForm['email'],
                $contactForm['phone'],
                $contactForm['preferred_location'],
                $contactForm['message']
            );
            set_flash('success', 'Your viewing request has been saved successfully.');
            redirect('index.php#contact');
        } catch (Throwable $exception) {
            $contactErrors[] = $exception->getMessage();
        }
    }
}

$properties = list_properties();
$featuredProperty = $properties[0] ?? null;
$propertyCount = count($properties);
$startingPrice = $propertyCount > 0 ? min(array_column($properties, 'price_ksh')) : 0;
$highestPrice = $propertyCount > 0 ? max(array_column($properties, 'price_ksh')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nyumbani Luxe | Modern Real Estate</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
    <div class="page-noise"></div>
    <main class="site-shell">
        <header class="topbar" id="home">
            <a class="brand" href="index.php">Nyumbani Luxe</a>
            <nav class="nav-links">
                <a href="#home">Home</a>
                <a href="#about">About</a>
                <a href="#listings">Listings</a>
                <a href="#contact">Contact</a>
            </nav>
            <div class="topbar-actions">
                <div class="phone-badge">
                    <span>Phone Us</span>
                    <strong>+254 712 345 678</strong>
                </div>
                <a class="outline-button" href="logout.php">Logout</a>
            </div>
        </header>

        <?php if ($flash !== null): ?>
            <div class="message-banner message-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <section class="hero">
            <div class="hero-copy">
                <p class="section-tag">Members Real Estate Portal</p>
                <h1>From elegant city homes to statement villas, find a modern space worth calling yours.</h1>
                <p class="hero-text">
                    Welcome, <?= e($user['full_name']) ?>. Explore stylish listings inspired by your design
                    reference, with curated pricing from KSh <?= e(number_format($startingPrice)) ?> to
                    KSh <?= e(number_format($highestPrice)) ?>.
                </p>
                <div class="hero-actions">
                    <a class="primary-button" href="#listings">Explore Listings</a>
                    <a class="outline-button" href="#contact">Book A Viewing</a>
                </div>
                <div class="hero-stats">
                    <article>
                        <strong><?= e((string) $propertyCount) ?></strong>
                        <span>Modern homes</span>
                    </article>
                    <article>
                        <strong>KSh <?= e(number_format($startingPrice)) ?></strong>
                        <span>Starting price</span>
                    </article>
                    <article>
                        <strong>100%</strong>
                        <span>Private access</span>
                    </article>
                </div>
            </div>

            <?php if ($featuredProperty !== null): ?>
                <div class="hero-visual">
                    <img src="<?= e($featuredProperty['image_path']) ?>" alt="<?= e($featuredProperty['title']) ?>">
                    <div class="floating-card">
                        <p>Featured Residence</p>
                        <h2><?= e($featuredProperty['title']) ?></h2>
                        <div class="mini-meta">
                            <span><?= e($featuredProperty['location']) ?></span>
                            <span>KSh <?= e(number_format((int) $featuredProperty['price_ksh'])) ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="hero-mark">DREAM HOMES</div>
        </section>

        <section class="story-grid" id="about">
            <article class="story-card">
                <p class="section-tag">About</p>
                <h2>Architecture-led homes for buyers who want bold, clean, premium spaces.</h2>
            </article>
            <article class="story-card muted">
                <p>
                    This portal uses your provided house images, a protected login system, and a
                    luxury editorial layout to present homes in a modern, polished way.
                </p>
            </article>
        </section>

        <section class="listings-section" id="listings">
            <div class="section-heading">
                <div>
                    <p class="section-tag">Property Collection</p>
                    <h2>Fancy modern houses listed with clear pricing.</h2>
                </div>
                <p class="section-copy">
                    Browse contemporary homes designed for comfort, strong curb appeal, and a premium
                    urban lifestyle.
                </p>
            </div>

            <div class="listings-grid">
                <?php foreach ($properties as $property): ?>
                    <article class="listing-card">
                        <div class="listing-image-wrap">
                            <img src="<?= e($property['image_path']) ?>" alt="<?= e($property['title']) ?>">
                            <span class="status-pill"><?= e($property['status_label']) ?></span>
                        </div>
                        <div class="listing-content">
                            <div class="listing-topline">
                                <p><?= e($property['location']) ?></p>
                                <strong>KSh <?= e(number_format((int) $property['price_ksh'])) ?></strong>
                            </div>
                            <h3><?= e($property['title']) ?></h3>
                            <p class="listing-description"><?= e($property['short_description']) ?></p>
                            <div class="listing-meta">
                                <span><?= e((string) $property['bedrooms']) ?> Beds</span>
                                <span><?= e((string) $property['bathrooms']) ?> Baths</span>
                                <span><?= e(number_format((int) $property['size_sqft'])) ?> Sq Ft</span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="services-panel">
            <div class="section-heading">
                <div>
                    <p class="section-tag">Services</p>
                    <h2>A polished buying experience for every client.</h2>
                </div>
            </div>
            <div class="service-grid">
                <article>
                    <h3>Curated Viewings</h3>
                    <p>We shortlist the right homes and arrange smooth property tours for serious buyers.</p>
                </article>
                <article>
                    <h3>Transparent Pricing</h3>
                    <p>Every listing is clearly presented with value-focused pricing between KSh 5M and 10M.</p>
                </article>
                <article>
                    <h3>Modern Living Focus</h3>
                    <p>Each property emphasizes current design, clean lines, and a premium residential feel.</p>
                </article>
            </div>
        </section>

        <section class="contact-banner" id="contact">
            <div class="contact-content">
                <p class="section-tag">Contact</p>
                <h2>Ready to step into your next home?</h2>
                <p>
                    Submit a viewing request and the PHP backend will validate your details, store the
                    form in MySQL, and help our team follow up on your preferred property.
                </p>
                <?php if ($contactErrors): ?>
                    <div class="message message-error contact-message"><?= e(implode(' ', $contactErrors)) ?></div>
                <?php endif; ?>

                <form method="post" class="contact-form">
                    <input type="hidden" name="form_type" value="viewing_request">
                    <div class="field-row">
                        <label class="field">
                            <span>Full Name</span>
                            <input type="text" name="full_name" value="<?= e($contactForm['full_name']) ?>" minlength="3" required>
                        </label>
                        <label class="field">
                            <span>Email Address</span>
                            <input type="email" name="email" value="<?= e($contactForm['email']) ?>" required>
                        </label>
                    </div>

                    <div class="field-row">
                        <label class="field">
                            <span>Phone Number</span>
                            <input type="text" name="phone" value="<?= e($contactForm['phone']) ?>" minlength="10" required>
                        </label>
                        <label class="field">
                            <span>Preferred Location</span>
                            <input type="text" name="preferred_location" value="<?= e($contactForm['preferred_location']) ?>" placeholder="Kilimani, Karen, Ruaka..." minlength="3" required>
                        </label>
                    </div>

                    <label class="field">
                        <span>Message</span>
                        <textarea name="message" rows="5" placeholder="Share the kind of home or viewing time you need." minlength="12" required><?= e($contactForm['message']) ?></textarea>
                    </label>

                    <button type="submit" class="primary-button">Send Viewing Request</button>
                </form>
            </div>
            <div class="contact-details">
                <strong>+254 712 345 678</strong>
                <span>hello@nyumbaniluxe.co.ke</span>
                <span>Nairobi, Kenya</span>
                <span>Form submissions are saved inside the database.</span>
            </div>
        </section>
    </main>
</body>
</html>

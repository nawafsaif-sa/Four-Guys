<header class="fg-header">
  <a class="fg-brand" href="<?= $fgBase ?>home.php" aria-label="Four Guys home"><span class="fg-logo-crop"><img src="<?= $fgBase ?>the.png" alt="Four Guys" width="190" height="68"></span></a>
  <button class="fg-menu-toggle" type="button" aria-expanded="false" aria-controls="fg-navigation" aria-label="Open navigation">☰</button>
  <nav class="fg-navigation" id="fg-navigation" aria-label="Main navigation">
    <a href="<?= $fgBase ?>home.php" data-page="home">Home</a><a href="<?= $fgBase ?>MenuD.php" data-page="menud">Our menu</a><a href="<?= $fgBase ?>About.html" data-page="about">Our story</a><a href="<?= $fgBase ?>contact.php" data-page="contact">Contact</a>
  </nav>
  <div class="fg-header-actions"><?php if (isset($_SESSION['client_id']) && (($_SESSION['role'] ?? '') === 'client')): ?><a class="fg-login" href="<?= $fgBase ?>Backend/logout_client.php">Log out</a><?php else: ?><a class="fg-login" href="<?= $fgBase ?>LoginD.php">Log in</a><?php endif; ?><a class="fg-cart" href="<?= $fgBase ?>cart.php" aria-label="Your cart"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M5 7h14l1 14H4L5 7Z"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/></svg><span>Your cart</span></a></div>
</header>
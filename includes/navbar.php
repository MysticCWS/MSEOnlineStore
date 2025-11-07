<nav class="navbar navstyle">
    <ul class="left-side">
        <li><a href="home.php">Home</a></li>
        <li><a href="products.php">Our Products</a></li>
        <li><a href="orders.php">My Orders</a></li>
        <li><a href="cyberstore.php">Cyberstore</a></li>
    </ul>
    <ul class="right-side">
        <?php if(isset($_SESSION['verified_user_id'])) : ?>
        <li><a href="profile.php" title="Edit Profile">
                <?php
                $uid = $_SESSION['verified_user_id'];
                try {
                    $user = $auth->getUser($uid);
                    echo $user->displayName;
                    } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                    echo $e->getMessage();
                }
                ?>

            </a>
        </li>
        <li>
            <a href="cart.php">
                <span class="bi-cart" title="Cart"></span>
            </a>
        </li>
        <li>
            <a href="function_logout.php">
                <span class="bi-power" title="Logout"></span>
            </a>
        </li>
        <?php else : ?>
        <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>

    <div class="hamburger" onclick="document.querySelector('.mobile-menu').classList.toggle('active');">
        <div></div><div></div><div></div>
    </div>
    <ul class="mobile-menu">
        <li><a href="home.php">Home</a></li>
        <li><a href="products.php">Our Products</a></li>
        <li><a href="orders.php">My Orders</a></li>
        <li><a href="cyberstore.php">Cyberstore</a></li>
        <?php if(isset($_SESSION['verified_user_id'])) : ?>
        <li>
            <a href="profile.php">
                <?php
                $uid = $_SESSION['verified_user_id'];
                try {
                    $user = $auth->getUser($uid);
                    echo $user->displayName;
                    echo " (Edit Profile)";
                } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                    echo $e->getMessage();
                }?>
            </a>
        </li>
        <li>
            <a href="cart.php">
                <span class="bi-cart" title="Cart"> (Cart)</span>
            </a>
        </li>
        <li>
            <a href="function_logout.php">
                <span class="bi-power" title="Logout"> (Logout)</span>
            </a>
        </li>
        <?php else : ?>
        <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="container">
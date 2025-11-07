<?php
session_start();
include 'dbcon.php';
include 'includes\header.php'; 
echo ' | Home';
include 'includes\header2.php';
include 'includes\navbar.php';

// Fetch carousel images from Firebase
$carouselRef = 'carousel';
$carouselImages = $database->getReference($carouselRef)->getValue();
?>

<script>
    // Wait until the DOM is fully loaded
    document.addEventListener("DOMContentLoaded", function() {
        const msg = document.getElementById("statusMessage");
        if (msg) {
            setTimeout(() => {
                msg.style.transition = "opacity 0.5s ease";
                msg.style.opacity = "0";
                setTimeout(() => msg.remove(), 500);
            }, 5000);
        }
    });
</script>

<div class="content">
    <!--Show Status-->
    <?php
        if(isset($_SESSION['status'])){
            echo "<h5 id='statusMessage' class='alert alert-success'>".$_SESSION['status']."</h5>";
            unset($_SESSION['status']);
        }
    ?>

    <div class="title">
        <h2>Welcome to MSE Online Store! </h2>
    </div>

    <div class="container col-md mx-auto px-4 py-4 border rounded bg-white">
        <div class="title">
            <h2 class="mb-3">Latest Promotions</h2>
        </div>
        <!-- Carousel Section -->
        <?php if (!empty($carouselImages)): ?>
            <div id="homepageCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
                <div class="carousel-inner rounded shadow" data-bs-interval="4000">
                    <?php 
                        $first = true;
                        foreach ($carouselImages as $imgKey => $imgData): 
                            if (empty($imgData) || !isset($imgData['imageUrl'])) continue;
                    ?>
                        <div class="carousel-item <?= $first ? 'active' : '' ?>">
                            <img src="<?= htmlspecialchars($imgData['imageUrl']); ?>" 
                                class="d-block w-100" 
                                style="max-height: 420px; object-fit: cover;"
                                alt="Promotion <?= htmlspecialchars($imgKey); ?>">
                        </div>
                    <?php 
                        $first = false;
                        endforeach; 
                    ?>
                </div>

                <!-- Controls -->
                <button class="carousel-control-prev" type="button" data-bs-target="#homepageCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#homepageCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>

                <!-- Indicators -->
                <div class="carousel-indicators">
                    <?php 
                        $i = 0;
                        foreach ($carouselImages as $imgKey => $imgData): 
                            if (empty($imgData) || !isset($imgData['imageUrl'])) continue;
                    ?>
                    <button type="button" data-bs-target="#homepageCarousel" data-bs-slide-to="<?= $i ?>" 
                            class="<?= $i === 0 ? 'active' : '' ?>" 
                            aria-label="Slide <?= $i + 1 ?>"></button>
                    <?php $i++; endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">No promotional images currently available.</div>
        <?php endif; ?>

        <div class="title">
            <h2 class="mb-3">About Us</h2>
        </div>
            
        <div class="">
            <p>
                <justify>
                    Welcome to MSE Online Store, your trusted destination for premium PC components, accessories, and laptops. 
                    We are passionate about technology and committed to providing our customers with top-quality products that meet 
                    their computing needs. Whether you are a gamer, a content creator, a professional, or a DIY enthusiast, MSE Online
                    Store has the tools and devices to take your digital experience to the next level.
                </justify>
            </p>
            <p>
                <justify>
                    At MSE Online Store, we offer a wide range of products, including:
                    <ul>
                        <li>
                            PC Components: From powerful processors, high-performance motherboards, and robust graphics cards, to efficient 
                            cooling solutions and memory modules, we provide the essential building blocks for your custom PC. Our products 
                            are sourced from renowned brands to ensure reliability and performance.
                        </li><br>
                        <li>
                            Laptop Solutions: Whether you’re looking for a high-end gaming laptop, a portable workhorse, or a sleek ultrabook, 
                            we offer an array of laptops designed to suit every need. Our laptops come with cutting-edge specs and features 
                            to enhance your productivity, entertainment, and everything in between.
                        </li><br>
                        <li>
                            PC Accessories: Complete your setup with our wide selection of accessories, including mechanical keyboards, gaming 
                            mice, monitors, gaming chairs, speakers, headsets, and more. We carry products designed for both comfort and 
                            performance, ensuring that you have everything you need for a seamless experience.
                        </li><br>
                    </ul>
                </justify>
            </p>
            <p>
                <justify>
                    Why Choose Us?
                    <ol>
                        <li>
                            Quality and Trust: We only offer products from well-established, trusted brands that are known for their performance 
                            and durability. Our goal is to ensure that you get the best value for your investment.
                        </li>
                        <li>
                            Customer-Centric Service: At MSE Online Store, we are dedicated to providing an exceptional customer experience. 
                            Our knowledgeable and friendly staff is here to assist you with product recommendations, technical support, and any 
                            questions you might have. We value your satisfaction and aim to create lasting relationships with our customers.
                        </li>
                        <li>
                            Competitive Pricing: We believe that high-quality technology should be accessible to everyone. That's why we strive 
                            to offer competitive prices on all our products, making it easier for you to build or upgrade your setup without 
                            breaking the bank.
                        </li>
                        <li>
                            Fast and Reliable Shipping: We understand that you want your new components and devices as soon as possible. That’s 
                            why we offer fast and reliable shipping options to ensure that your purchases arrive safely and promptly.
                        </li>
                        <li>
                            Wide Product Selection: Our store offers a diverse range of products catering to all types of tech enthusiasts. 
                            Whether you're building a gaming rig, upgrading your workstation, or just looking for the best accessories, Mystic 
                            Star Enterprise has something for you.
                        </li>
                    </ol>
                </justify>
            </p>
            <p>
                <justify>
                    At MSE Online Store, we are not just about selling products - we are about building a community of tech lovers who share 
                    a passion for performance, quality, and innovation.
                </justify>
            </p>
            <p>
                <justify>
                    Thank you for choosing MSE Online Store as your go-to destination for all things technology. Let us help you unlock 
                    the true potential of your computing experience!
                </justify>
            </p>
            <p>
                <justify>
                    Feel free to reach out to us with any questions or concerns. Our team is ready to assist you in finding the perfect solutions 
                    for your needs.
                </justify>
            </p>
        </div>
    </div>

</div>

<?php
include 'includes\footer.php';
?>
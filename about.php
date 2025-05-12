<?php
include 'includes/header.php';
?>

<div class="about-section">
    <div class="container">
        <div class="about-header">
            <h1>About WattzElec</h1>
            <p class="lead">Your Trusted Partner in Electronic Solutions</p>
        </div>

        <div class="about-content">
            <div class="row">
                <div class="col-md-6">
                    <div class="about-story">
                        <h2>Our Story</h2>
                        <p>Founded in 2023, Wattz Electronics is a small electronics store. We're passionate about bringing quality electronic products and exceptional service to our customers.</p>
                        <p>Our commitment to excellence and customer satisfaction has made us a trusted name in the industry, serving both individual consumers and businesses across the country.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="about-image">
                        <img src="images/about/store_front.jpg" alt="WattzElec Store" class="img-fluid rounded shadow">
                    </div>
                </div>
            </div>

            <div class="values-section">
                <h2>Our Core Values</h2>
                <div class="row">
                    <div class="col-md-4">
                        <div class="value-card">
                            <i class="fas fa-check-circle"></i>
                            <h3>Quality</h3>
                            <p>We source only the best products from trusted manufacturers and provide genuine warranties.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="value-card">
                            <i class="fas fa-handshake"></i>
                            <h3>Integrity</h3>
                            <p>Honest pricing, transparent business practices, and reliable customer service.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="value-card">
                            <i class="fas fa-heart"></i>
                            <h3>Customer First</h3>
                            <p>Your satisfaction is our priority. We're here to help you make informed decisions.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="why-choose-section">
                <h2>Why Choose WattzElec?</h2>
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="feature-card">
                            <i class="fas fa-shipping-fast"></i>
                            <h4>Fast Delivery</h4>
                            <p>Quick and reliable shipping across Kenya</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="feature-card">
                            <i class="fas fa-shield-alt"></i>
                            <h4>Secure Shopping</h4>
                            <p>Safe and secure payment options</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="feature-card">
                            <i class="fas fa-headset"></i>
                            <h4>24/7 Support</h4>
                            <p>Always here to help you</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="feature-card">
                            <i class="fas fa-undo"></i>
                            <h4>Easy Returns</h4>
                            <p>Hassle-free return policy</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-section">
                <h2>Visit Us</h2>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="contact-info">
                            <h4>Our Location</h4>
                            <p><i class="fas fa-map-marker-alt"></i> Ground Floor, Kadiga Plaza, Off Kamiti Road, Lumumba Dr, Nairobi</p>
                            <p><i class="fas fa-phone"></i> +254 716295653</p>
                            <p><i class="fas fa-envelope"></i> wattzelectronics@gmail.com</p>
                            <p><i class="fas fa-clock"></i> Monday - Saturday: 9:00 AM - 6:00 PM</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="map-container">
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d15955.256233264317!2d36.8868283!3d-1.2191283!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f3f8d7c5d8b7d%3A0x2b5f7c76ee9dd2e2!2sKadiga%20Plaza!5e0!3m2!1sen!2ske!4v1682319658774!5m2!1sen!2ske"
                                width="100%" 
                                height="300" 
                                style="border:0; border-radius: 10px;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.about-section {
    padding: 4rem 0;
    background-color: #f8f9fa;
}

.about-header {
    text-align: center;
    margin-bottom: 3rem;
}

.about-header h1 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

.about-header .lead {
    color: #666;
    font-size: 1.25rem;
}

.about-story {
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    height: 100%;
}

.about-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.values-section {
    margin: 4rem 0;
    text-align: center;
}

.value-card {
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 1rem 0;
    height: 100%;
    transition: transform 0.3s ease;
}

.value-card:hover {
    transform: translateY(-5px);
}

.value-card i {
    font-size: 2.5rem;
    color: #3498db;
    margin-bottom: 1rem;
}

.why-choose-section {
    margin: 4rem 0;
    text-align: center;
}

.feature-card {
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 1rem 0;
    height: 100%;
    transition: transform 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
}

.feature-card i {
    font-size: 2rem;
    color: #3498db;
    margin-bottom: 1rem;
}

.contact-section {
    margin: 4rem 0;
    text-align: center;
}

.contact-info {
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: left;
    height: 100%;
}

.map-container {
    height: 100%;
    min-height: 300px;
    padding: 1rem;
}

h2 {
    color: #2c3e50;
    margin-bottom: 2rem;
    text-align: center;
}

h3 {
    color: #2c3e50;
    margin-bottom: 1rem;
}

h4 {
    color: #2c3e50;
    margin: 1rem 0;
}

p {
    color: #666;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .about-section {
        padding: 2rem 0;
    }

    .about-story {
        margin-bottom: 2rem;
    }

    .value-card, .feature-card {
        margin-bottom: 1rem;
    }

    .contact-info {
        margin-bottom: 2rem;
    }
}
</style>

<?php
include 'includes/footer.php';
?>

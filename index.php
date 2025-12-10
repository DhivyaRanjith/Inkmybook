<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden">
    <div class="hero-blob hero-blob-1"></div>
    <div class="hero-blob hero-blob-2"></div>

    <div class="container position-relative z-1">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="badge bg-white text-primary shadow-sm rounded-pill px-3 py-2 mb-4 animate-fade-in">
                    <i class="fas fa-star me-2 text-warning"></i> #1 Marketplace for Freelancers
                </div>
                <h1 class="display-3 fw-bold mb-4 lh-sm animate-fade-in delay-100">
                    Find the perfect <br>
                    <span class="gradient-text typing-text">freelance services</span><span
                        class="typing-cursor">|</span><br>
                    for your business.
                </h1>
                <p class="lead text-muted mb-5 animate-fade-in delay-200" style="max-width: 500px;">
                    Connect with top-rated freelancers for your project. Secure payments, 24/7 support, and satisfaction
                    guaranteed.
                </p>

                <div class="bg-white p-2 rounded-pill shadow-lg d-flex align-items-center animate-fade-in delay-300"
                    style="max-width: 500px;">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-0 ps-4"><i
                                class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-0 shadow-none"
                            placeholder="What service are you looking for?">
                        <button class="btn btn-primary rounded-pill px-4 fw-bold">Search</button>
                    </div>
                </div>

                <div class="mt-4 animate-fade-in delay-400">
                    <small class="text-muted fw-bold me-2">Popular:</small>
                    <a href="modules/services/browse.php?category=design"
                        class="badge bg-light text-dark border me-1 text-decoration-none hover-scale">Web Design</a>
                    <a href="modules/services/browse.php?category=writing"
                        class="badge bg-light text-dark border me-1 text-decoration-none hover-scale">Content
                        Writing</a>
                    <a href="modules/services/browse.php?category=marketing"
                        class="badge bg-light text-dark border me-1 text-decoration-none hover-scale">SEO</a>
                </div>
            </div>
            <div class="col-lg-6 text-center animate-fade-in delay-500">
                <div class="position-relative d-inline-block">
                    <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                        alt="Freelancer" class="img-fluid rounded-4 shadow-lg position-relative z-2"
                        style="max-height: 500px; border: 10px solid rgba(255,255,255,0.5);">
                    <!-- Floating Cards -->
                    <div class="card position-absolute top-0 start-0 translate-middle-y shadow-lg border-0 rounded-4 p-3 z-3 animate-float glass-effect"
                        style="width: 200px; margin-top: 100px; margin-left: -50px;">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3 text-success">
                                <i class="fas fa-check-circle fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Project Done</h6>
                                <small class="text-muted">Just now</small>
                            </div>
                        </div>
                    </div>
                    <div class="card position-absolute bottom-0 end-0 translate-middle-y shadow-lg border-0 rounded-4 p-3 z-3 animate-float delay-1000 glass-effect"
                        style="width: 180px; margin-bottom: 50px; margin-right: -30px;">
                        <div class="d-flex align-items-center">
                            <div class="avatar-group me-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px; font-weight: bold;">4.9</div>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Rating</h6>
                                <div class="text-warning small"><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                                        class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trusted By -->
<section class="py-4 bg-light border-bottom">
    <div class="container">
        <p class="text-center text-muted small fw-bold text-uppercase letter-spacing-2 mb-4">Trusted by leading brands
        </p>
        <div class="row justify-content-center align-items-center opacity-50 grayscale-hover transition-all">
            <div class="col-4 col-md-2 text-center mb-3 mb-md-0">
                <h5 class="fw-bold text-muted mb-0"><i class="fab fa-google me-2"></i>Google</h5>
            </div>
            <div class="col-4 col-md-2 text-center mb-3 mb-md-0">
                <h5 class="fw-bold text-muted mb-0"><i class="fab fa-facebook me-2"></i>Meta</h5>
            </div>
            <div class="col-4 col-md-2 text-center mb-3 mb-md-0">
                <h5 class="fw-bold text-muted mb-0"><i class="fab fa-amazon me-2"></i>Amazon</h5>
            </div>
            <div class="col-4 col-md-2 text-center mb-3 mb-md-0">
                <h5 class="fw-bold text-muted mb-0"><i class="fab fa-microsoft me-2"></i>Microsoft</h5>
            </div>
            <div class="col-4 col-md-2 text-center mb-3 mb-md-0">
                <h5 class="fw-bold text-muted mb-0"><i class="fab fa-airbnb me-2"></i>Airbnb</h5>
            </div>
        </div>
    </div>
</section>

<!-- Popular Services -->
<section class="py-5 section-padding">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h2 class="fw-bold display-6 mb-2">Popular Services</h2>
                <p class="text-muted lead mb-0">Get your project done by experts in these fields.</p>
            </div>
            <a href="modules/services/browse.php" class="btn btn-outline-primary rounded-pill fw-bold">View All
                Services</a>
        </div>

        <div class="row g-4">
            <!-- Category Card 1 -->
            <div class="col-md-3">
                <a href="modules/services/browse.php?category=graphics"
                    class="card h-100 border-0 shadow-sm card-hover-premium text-decoration-none">
                    <div class="card-body p-4 text-center">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                            style="width: 80px; height: 80px; font-size: 2rem;">
                            <i class="fas fa-paint-brush"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Graphics & Design</h5>
                        <p class="text-muted small mb-0">Logo, Brand Identity, UI/UX</p>
                    </div>
                </a>
            </div>
            <!-- Category Card 2 -->
            <div class="col-md-3">
                <a href="modules/services/browse.php?category=programming"
                    class="card h-100 border-0 shadow-sm card-hover-premium text-decoration-none">
                    <div class="card-body p-4 text-center">
                        <div class="icon-box bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                            style="width: 80px; height: 80px; font-size: 2rem;">
                            <i class="fas fa-code"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Programming</h5>
                        <p class="text-muted small mb-0">WordPress, Web, Mobile Apps</p>
                    </div>
                </a>
            </div>
            <!-- Category Card 3 -->
            <div class="col-md-3">
                <a href="modules/services/browse.php?category=marketing"
                    class="card h-100 border-0 shadow-sm card-hover-premium text-decoration-none">
                    <div class="card-body p-4 text-center">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                            style="width: 80px; height: 80px; font-size: 2rem;">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Digital Marketing</h5>
                        <p class="text-muted small mb-0">SEO, Social Media, Ads</p>
                    </div>
                </a>
            </div>
            <!-- Category Card 4 -->
            <div class="col-md-3">
                <a href="modules/services/browse.php?category=writing"
                    class="card h-100 border-0 shadow-sm card-hover-premium text-decoration-none">
                    <div class="card-body p-4 text-center">
                        <div class="icon-box bg-info bg-opacity-10 text-info rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                            style="width: 80px; height: 80px; font-size: 2rem;">
                            <i class="fas fa-pen-nib"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Writing & Translation</h5>
                        <p class="text-muted small mb-0">Articles, Copywriting, Books</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <img src="https://img.freepik.com/free-vector/team-checklist-concept-illustration_114360-10325.jpg"
                    alt="Features" class="img-fluid rounded-4 shadow-lg hover-scale">
            </div>
            <div class="col-lg-6 ps-lg-5">
                <h2 class="fw-bold display-6 mb-4">A whole world of freelance talent at your fingertips</h2>

                <div class="d-flex mb-4">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-primary text-white p-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="fas fa-check small"></i>
                        </div>
                    </div>
                    <div class="ms-3">
                        <h5 class="fw-bold">The best for every budget</h5>
                        <p class="text-muted">Find high-quality services at every price point. No hourly rates, just
                            project-based pricing.</p>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-primary text-white p-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="fas fa-check small"></i>
                        </div>
                    </div>
                    <div class="ms-3">
                        <h5 class="fw-bold">Quality work done quickly</h5>
                        <p class="text-muted">Find the right freelancer to begin working on your project within minutes.
                        </p>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-primary text-white p-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="fas fa-check small"></i>
                        </div>
                    </div>
                    <div class="ms-3">
                        <h5 class="fw-bold">Protected payments, every time</h5>
                        <p class="text-muted">Always know what you'll pay upfront. Your payment isn't released until you
                            approve the work.</p>
                    </div>
                </div>

                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-primary text-white p-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="fas fa-check small"></i>
                        </div>
                    </div>
                    <div class="ms-3">
                        <h5 class="fw-bold">24/7 support</h5>
                        <p class="text-muted">Questions? Our round-the-clock support team is available to help anytime,
                            anywhere.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Counter -->
<section class="py-5 bg-dark text-white position-relative overflow-hidden">
    <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10"
        style="background: url('https://www.transparenttextures.com/patterns/cubes.png');"></div>
    <div class="container position-relative z-1 py-4">
        <div class="row text-center">
            <div class="col-md-3 mb-4 mb-md-0">
                <h2 class="display-4 fw-bold text-primary mb-0 counter" data-target="5000">0</h2>
                <p class="lead opacity-75">Freelancers</p>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h2 class="display-4 fw-bold text-primary mb-0 counter" data-target="12000">0</h2>
                <p class="lead opacity-75">Completed Projects</p>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h2 class="display-4 fw-bold text-primary mb-0 counter" data-target="4.9">0</h2>
                <p class="lead opacity-75">Average Rating</p>
            </div>
            <div class="col-md-3">
                <h2 class="display-4 fw-bold text-primary mb-0 counter" data-target="24">0</h2>
                <p class="lead opacity-75">Support Hours</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5 section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold display-6">What our users say</h2>
            <p class="text-muted lead">Don't just take our word for it.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 p-4 rounded-4 card-hover-premium">
                    <div class="mb-3 text-warning">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                            class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="text-muted mb-4">"InkMyBook changed the way we work. We found an amazing graphic designer
                        within hours!"</p>
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold me-3"
                            style="width: 48px; height: 48px;">JD</div>
                        <div>
                            <h6 class="fw-bold mb-0">John Doe</h6>
                            <small class="text-muted">CEO, TechStart</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 p-4 rounded-4 card-hover-premium">
                    <div class="mb-3 text-warning">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                            class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="text-muted mb-4">"As a freelancer, this platform gives me the freedom to work on projects
                        I love. Payments are always on time."</p>
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold me-3"
                            style="width: 48px; height: 48px;">JS</div>
                        <div>
                            <h6 class="fw-bold mb-0">Jane Smith</h6>
                            <small class="text-muted">Content Writer</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 p-4 rounded-4 card-hover-premium">
                    <div class="mb-3 text-warning">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                            class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="text-muted mb-4">"The support team is incredible. They helped me resolve a dispute quickly
                        and fairly. Highly recommended!"</p>
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center fw-bold me-3"
                            style="width: 48px; height: 48px;">MR</div>
                        <div>
                            <h6 class="fw-bold mb-0">Mike Ross</h6>
                            <small class="text-muted">Marketing Director</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container py-4">
        <h2 class="display-5 fw-bold mb-4">Ready to get started?</h2>
        <p class="lead mb-5 opacity-75">Join thousands of freelancers and businesses today.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="modules/auth/register.php?role=seeker"
                class="btn btn-light btn-lg rounded-pill px-5 fw-bold text-primary shadow-lg hover-scale">Hire
                Talent</a>
            <a href="modules/auth/register.php?role=provider"
                class="btn btn-outline-light btn-lg rounded-pill px-5 fw-bold hover-scale">Become a Seller</a>
        </div>
    </div>
</section>

<style>
    /* Custom Animations & Styles for Home Page */
    .hover-lift {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }

    .hover-scale {
        transition: transform 0.3s ease;
    }

    .hover-scale:hover {
        transform: scale(1.05);
    }

    .grayscale-hover {
        filter: grayscale(100%);
        transition: filter 0.3s ease;
    }

    .grayscale-hover:hover {
        filter: grayscale(0%);
        opacity: 1 !important;
    }

    .animate-float {
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
        0% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-10px);
        }

        100% {
            transform: translateY(0px);
        }
    }

    .delay-100 {
        animation-delay: 0.1s;
    }

    .delay-200 {
        animation-delay: 0.2s;
    }

    .delay-300 {
        animation-delay: 0.3s;
    }

    .delay-400 {
        animation-delay: 0.4s;
    }

    .delay-500 {
        animation-delay: 0.5s;
    }

    .delay-1000 {
        animation-delay: 1s;
    }
</style>

<script>
    // Typing Effect
    const textElement = document.querySelector('.typing-text');
    const words = ['freelance services', 'web developers', 'graphic designers', 'content writers'];
    let wordIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    let typeSpeed = 100;

    function type() {
        const currentWord = words[wordIndex];
        if (isDeleting) {
            textElement.textContent = currentWord.substring(0, charIndex - 1);
            charIndex--;
            typeSpeed = 50;
        } else {
            textElement.textContent = currentWord.substring(0, charIndex + 1);
            charIndex++;
            typeSpeed = 100;
        }
        if (!isDeleting && charIndex === currentWord.length) {
            isDeleting = true;
            typeSpeed = 2000;
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            wordIndex = (wordIndex + 1) % words.length;
            typeSpeed = 500;
        }
        setTimeout(type, typeSpeed);
    }
    document.addEventListener('DOMContentLoaded', type);

    // Counter Animation
    const counters = document.querySelectorAll('.counter');
    const speed = 200;
    const animateCounters = () => {
        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const inc = target / speed;
                if (count < target) {
                    counter.innerText = Math.ceil(count + inc);
                    setTimeout(updateCount, 20);
                } else {
                    counter.innerText = target;
                }
            };
            updateCount();
        });
    };

    // Trigger counter when in view
    let counterTriggered = false;
    window.addEventListener('scroll', () => {
        const section = document.querySelector('.bg-dark');
        if (section && !counterTriggered) {
            const sectionTop = section.getBoundingClientRect().top;
            if (sectionTop < window.innerHeight - 100) {
                animateCounters();
                counterTriggered = true;
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
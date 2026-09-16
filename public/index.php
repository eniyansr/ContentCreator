<?php
/**
 * CreatorAI - Landing Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';

$pageTitle = 'CreatorAI – Your Intelligent AI Partner for Content Creation';
$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="CreatorAI is an intelligent AI-powered platform that helps content creators plan, generate, optimize, and grow their content across YouTube, Instagram, TikTok, LinkedIn, and more.">
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/landing.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/responsive.css'); ?>">
</head>
<body>
    <!-- Navigation -->
    <nav class="landing-nav" id="mainNav">
        <div class="nav-container">
            <a href="<?php echo base_url('public/'); ?>" class="nav-logo">
                <div class="nav-logo-icon">✦</div>
                CreatorAI
            </a>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#platforms">Platforms</a></li>
                <li><a href="#capabilities">AI Capabilities</a></li>
                <li><a href="#about">About</a></li>
            </ul>
            <div class="nav-cta">
                <a href="<?php echo base_url('public/login.php'); ?>" class="btn btn-ghost">Login</a>
                <a href="<?php echo base_url('public/register.php'); ?>" class="btn btn-primary">Get Started</a>
            </div>
            <button class="nav-mobile-toggle" onclick="toggleMobileMenu()" aria-label="Menu">☰</button>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div class="nav-mobile-menu" id="mobileMenu">
        <button class="nav-mobile-close" onclick="toggleMobileMenu()">×</button>
        <a href="#features" onclick="toggleMobileMenu()">Features</a>
        <a href="#how-it-works" onclick="toggleMobileMenu()">How It Works</a>
        <a href="#platforms" onclick="toggleMobileMenu()">Platforms</a>
        <a href="#capabilities" onclick="toggleMobileMenu()">AI Capabilities</a>
        <a href="<?php echo base_url('public/login.php'); ?>">Login</a>
        <a href="<?php echo base_url('public/register.php'); ?>" class="btn btn-primary btn-lg">Get Started</a>
    </div>

    <!-- Hero Section -->
    <section class="hero" id="hero">
        <div class="hero-particles" id="heroParticles"></div>
        <div class="hero-content">
            <div class="hero-badge">
                <span>✦</span> AI-Powered Content Creation Platform
            </div>
            <h1>
                Your Intelligent AI Partner for <span class="gradient-text">Content Creation</span>
            </h1>
            <p class="hero-subtitle">
                CreatorAI helps content creators plan, generate, optimize, and grow their content across all major platforms. 
                Powered by advanced AI, personalized to your unique creator profile.
            </p>
            <div class="hero-buttons">
                <a href="<?php echo base_url('public/register.php'); ?>" class="btn btn-primary btn-lg">
                    🚀 Get Started Free
                </a>
                <a href="<?php echo base_url('public/login.php'); ?>" class="btn btn-secondary btn-lg">
                    Creator Login
                </a>
                <a href="<?php echo base_url('admin/login.php'); ?>" class="btn btn-ghost btn-lg">
                    Admin Login →
                </a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-value">29+</div>
                    <div class="hero-stat-label">AI Features</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">10+</div>
                    <div class="hero-stat-label">Platforms</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">8</div>
                    <div class="hero-stat-label">Content Types</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value">∞</div>
                    <div class="hero-stat-label">Possibilities</div>
                </div>
            </div>
        </div>
    </section>

    <!-- What CreatorAI Does -->
    <section class="section" id="what-it-does" style="background: var(--bg-secondary);">
        <div class="section-container">
            <div class="section-header animate-in">
                <span class="section-badge">What CreatorAI Does</span>
                <h2 class="section-title">Your Complete Content Creation Operating System</h2>
                <p class="section-subtitle">CreatorAI understands your unique creator profile, audience, and brand voice to deliver personalized AI content assistance.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card animate-in">
                    <div class="feature-icon">🧠</div>
                    <h3 class="feature-title">Personalized AI</h3>
                    <p class="feature-desc">AI that knows your niche, audience, brand voice, and goals. Every response is tailored to your unique creator identity.</p>
                </div>
                <div class="feature-card animate-in">
                    <div class="feature-icon">✍️</div>
                    <h3 class="feature-title">Content Generation</h3>
                    <p class="feature-desc">Generate scripts, captions, titles, descriptions, hooks, CTAs, hashtags, and complete content packages with one click.</p>
                </div>
                <div class="feature-card animate-in">
                    <div class="feature-icon">💡</div>
                    <h3 class="feature-title">AI Idea Lab</h3>
                    <p class="feature-desc">Never run out of content ideas. AI generates platform-specific ideas based on your niche, trends, and audience interests.</p>
                </div>
                <div class="feature-card animate-in">
                    <div class="feature-icon">🔄</div>
                    <h3 class="feature-title">Content Repurposing</h3>
                    <p class="feature-desc">Transform one piece of content into platform-optimized versions for YouTube, Instagram, TikTok, LinkedIn, and more.</p>
                </div>
                <div class="feature-card animate-in">
                    <div class="feature-icon">📊</div>
                    <h3 class="feature-title">Content Analyzer</h3>
                    <p class="feature-desc">Score your content across 8 dimensions including hook strength, engagement potential, SEO, and brand consistency.</p>
                </div>
                <div class="feature-card animate-in">
                    <div class="feature-icon">📅</div>
                    <h3 class="feature-title">Content Calendar</h3>
                    <p class="feature-desc">Plan, schedule, and manage your content calendar. AI can auto-generate your weekly or monthly content plan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="section" id="features">
        <div class="section-container">
            <div class="section-header animate-in">
                <span class="section-badge">Features</span>
                <h2 class="section-title">Everything a Content Creator Needs</h2>
                <p class="section-subtitle">From ideation to analytics, CreatorAI covers your entire content creation workflow.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card animate-in">
                    <div class="feature-icon">🎯</div>
                    <h3 class="feature-title">Creator Profiling</h3>
                    <p class="feature-desc">Build a detailed creator profile with your niche, audience, brand voice, goals, and preferences for truly personalized AI.</p>
                </div>
                <div class="feature-card animate-in">
                    <div class="feature-icon">💬</div>
                    <h3 class="feature-title">AI Chat Assistant</h3>
                    <p class="feature-desc">Chat with an AI that understands your brand. Get advice, ideas, scripts, and strategies tailored to your audience.</p>
                </div>
                <div class="feature-card animate-in">
                    <div class="feature-icon">🎬</div>
                    <h3 class="feature-title">Script Generator</h3>
                    <p class="feature-desc">Generate video scripts with hooks, talking points, B-roll suggestions, and CTAs for any platform.</p>
                </div>
                <div class="feature-card animate-in">
                    <div class="feature-icon">📈</div>
                    <h3 class="feature-title">Performance Analytics</h3>
                    <p class="feature-desc">Track your content performance and get AI-powered insights on what works best for your audience.</p>
                </div>
                <div class="feature-card animate-in">
                    <div class="feature-icon">🏷️</div>
                    <h3 class="feature-title">SEO & Hashtags</h3>
                    <p class="feature-desc">Generate optimized titles, descriptions, tags, keywords, and hashtags for maximum discoverability.</p>
                </div>
                <div class="feature-card animate-in">
                    <div class="feature-icon">📚</div>
                    <h3 class="feature-title">Content Library</h3>
                    <p class="feature-desc">Save, organize, and reuse your generated content. Build your own library of scripts, ideas, and templates.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="section" id="how-it-works" style="background: var(--bg-secondary);">
        <div class="section-container">
            <div class="section-header animate-in">
                <span class="section-badge">How It Works</span>
                <h2 class="section-title">Get Started in 4 Simple Steps</h2>
                <p class="section-subtitle">From sign-up to AI-powered content, the process is seamless.</p>
            </div>
            <div class="steps-container animate-in">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-connector"></div>
                    <h3 class="step-title">Create Account</h3>
                    <p class="step-desc">Sign up in seconds with your email.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-connector"></div>
                    <h3 class="step-title">Build Profile</h3>
                    <p class="step-desc">Complete the 8-step onboarding wizard to define your creator identity.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-connector"></div>
                    <h3 class="step-title">Ask AI</h3>
                    <p class="step-desc">Chat with your personalized AI assistant or use structured generators.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3 class="step-title">Create & Grow</h3>
                    <p class="step-desc">Generate, analyze, repurpose, and optimize your content.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Supported Platforms -->
    <section class="section" id="platforms">
        <div class="section-container">
            <div class="section-header animate-in">
                <span class="section-badge">Platforms</span>
                <h2 class="section-title">Optimized for Every Platform</h2>
                <p class="section-subtitle">CreatorAI understands the unique requirements of each platform.</p>
            </div>
            <div class="platforms-grid animate-in">
                <div class="platform-card"><div class="platform-icon">▶️</div><div class="platform-name">YouTube</div></div>
                <div class="platform-card"><div class="platform-icon">📸</div><div class="platform-name">Instagram</div></div>
                <div class="platform-card"><div class="platform-icon">🎵</div><div class="platform-name">TikTok</div></div>
                <div class="platform-card"><div class="platform-icon">👥</div><div class="platform-name">Facebook</div></div>
                <div class="platform-card"><div class="platform-icon">𝕏</div><div class="platform-name">X / Twitter</div></div>
                <div class="platform-card"><div class="platform-icon">💼</div><div class="platform-name">LinkedIn</div></div>
                <div class="platform-card"><div class="platform-icon">📌</div><div class="platform-name">Pinterest</div></div>
                <div class="platform-card"><div class="platform-icon">🌐</div><div class="platform-name">Blog</div></div>
                <div class="platform-card"><div class="platform-icon">🎙️</div><div class="platform-name">Podcast</div></div>
                <div class="platform-card"><div class="platform-icon">✈️</div><div class="platform-name">Telegram</div></div>
                <div class="platform-card"><div class="platform-icon">📧</div><div class="platform-name">Newsletter</div></div>
                <div class="platform-card"><div class="platform-icon">➕</div><div class="platform-name">More...</div></div>
            </div>
        </div>
    </section>

    <!-- AI Capabilities -->
    <section class="section" id="capabilities" style="background: var(--bg-secondary);">
        <div class="section-container">
            <div class="section-header animate-in">
                <span class="section-badge">AI Capabilities</span>
                <h2 class="section-title">Powered by Advanced AI</h2>
                <p class="section-subtitle">Enterprise-grade AI technology, personalized for independent creators.</p>
            </div>
            <div class="capabilities-grid animate-in">
                <div class="capability-item"><div class="capability-icon">🎯</div><div><h4 class="capability-title">Personalized Context</h4><p class="capability-desc">AI uses your complete creator profile as context for every interaction.</p></div></div>
                <div class="capability-item"><div class="capability-icon">📝</div><div><h4 class="capability-title">Multi-Format Generation</h4><p class="capability-desc">Scripts, captions, titles, hooks, CTAs, hashtags, descriptions, and more.</p></div></div>
                <div class="capability-item"><div class="capability-icon">🔄</div><div><h4 class="capability-title">Smart Repurposing</h4><p class="capability-desc">Automatically adapt content for different platforms with proper formatting.</p></div></div>
                <div class="capability-item"><div class="capability-icon">📊</div><div><h4 class="capability-title">Content Scoring</h4><p class="capability-desc">8-dimension content analysis with actionable improvement recommendations.</p></div></div>
                <div class="capability-item"><div class="capability-icon">🧠</div><div><h4 class="capability-title">Creator Memory</h4><p class="capability-desc">AI remembers your preferences, brand voice, and content history.</p></div></div>
                <div class="capability-item"><div class="capability-icon">📅</div><div><h4 class="capability-title">Calendar Planning</h4><p class="capability-desc">AI-generated content calendars based on your goals and posting frequency.</p></div></div>
                <div class="capability-item"><div class="capability-icon">💡</div><div><h4 class="capability-title">Trend-Aware Ideas</h4><p class="capability-desc">Content ideas that consider your niche, audience, and current trends.</p></div></div>
                <div class="capability-item"><div class="capability-icon">📈</div><div><h4 class="capability-title">Performance Insights</h4><p class="capability-desc">Data-driven recommendations based on your content performance history.</p></div></div>
            </div>
        </div>
    </section>

    <!-- About -->
    <section class="section" id="about">
        <div class="section-container">
            <div class="section-header animate-in">
                <span class="section-badge">About The Project</span>
                <h2 class="section-title">Phase 2 – AI-Powered Research Platform</h2>
                <p class="section-subtitle">CreatorAI is Phase 2 of a research project studying how AI can transform content creation for digital creators. It combines web development, database design, AI integration, and natural language processing into a production-quality platform.</p>
            </div>
            <div class="features-grid animate-in">
                <div class="feature-card" style="text-align: center;">
                    <div class="feature-icon" style="margin: 0 auto var(--space-5);">🏗️</div>
                    <h3 class="feature-title">Full-Stack Architecture</h3>
                    <p class="feature-desc">PHP + MySQL + Python + JavaScript working together through clean REST APIs.</p>
                </div>
                <div class="feature-card" style="text-align: center;">
                    <div class="feature-icon" style="margin: 0 auto var(--space-5);">🔒</div>
                    <h3 class="feature-title">Security First</h3>
                    <p class="feature-desc">PDO prepared statements, CSRF protection, password hashing, role-based access control.</p>
                </div>
                <div class="feature-card" style="text-align: center;">
                    <div class="feature-icon" style="margin: 0 auto var(--space-5);">🚀</div>
                    <h3 class="feature-title">Future Ready</h3>
                    <p class="feature-desc">Designed for extensibility with API integrations, RAG architecture, and automated publishing.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section" id="cta">
        <div class="cta-content animate-in">
            <h2>Ready to Transform Your Content Creation?</h2>
            <p>Join CreatorAI and experience the power of AI personalized to your unique creator identity.</p>
            <div class="hero-buttons">
                <a href="<?php echo base_url('public/register.php'); ?>" class="btn btn-primary btn-lg">🚀 Create Your Account</a>
                <a href="<?php echo base_url('public/login.php'); ?>" class="btn btn-secondary btn-lg">Sign In</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="footer-container">
            <div class="footer-brand">
                <a href="<?php echo base_url('public/'); ?>" class="nav-logo">
                    <div class="nav-logo-icon">✦</div>
                    CreatorAI
                </a>
                <p>Your Intelligent AI Partner for Content Creation. A Phase 2 research project for digital content creators.</p>
            </div>
            <div>
                <h4 class="footer-heading">Platform</h4>
                <ul class="footer-links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#capabilities">AI Capabilities</a></li>
                    <li><a href="#platforms">Platforms</a></li>
                    <li><a href="#how-it-works">How It Works</a></li>
                </ul>
            </div>
            <div>
                <h4 class="footer-heading">Creators</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo base_url('public/register.php'); ?>">Get Started</a></li>
                    <li><a href="<?php echo base_url('public/login.php'); ?>">Creator Login</a></li>
                    <li><a href="#about">About</a></li>
                </ul>
            </div>
            <div>
                <h4 class="footer-heading">Admin</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo base_url('admin/login.php'); ?>">Admin Login</a></li>
                    <li><a href="<?php echo base_url('admin/register.php'); ?>">Admin Register</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> CreatorAI — Intelligent AI-Powered Content Creator Assistant. All rights reserved.
        </div>
    </footer>

    <script src="<?php echo asset_url('js/utils.js'); ?>"></script>
    <script>
        // Nav scroll effect
        window.addEventListener('scroll', () => {
            document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
        });

        // Mobile menu
        function toggleMobileMenu() {
            document.getElementById('mobileMenu').classList.toggle('active');
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const target = document.querySelector(a.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Hero particles
        (function createParticles() {
            const container = document.getElementById('heroParticles');
            for (let i = 0; i < 30; i++) {
                const p = document.createElement('div');
                p.className = 'hero-particle';
                p.style.left = Math.random() * 100 + '%';
                p.style.top = Math.random() * 100 + '%';
                p.style.setProperty('--duration', (4 + Math.random() * 8) + 's');
                p.style.animationDelay = Math.random() * 5 + 's';
                p.style.opacity = 0.1 + Math.random() * 0.3;
                p.style.width = p.style.height = (2 + Math.random() * 4) + 'px';
                container.appendChild(p);
            }
        })();

        // Scroll animations
        Utils.initScrollAnimations();
    </script>
</body>
</html>

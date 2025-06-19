<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <a href="home_first.html"><img src="images/logo_pwc.png" alt="PawConnect Logo" class="logo"></a>
        <div class="header-content">
          <h1 class="header-title">PawConnect</h1>
        </div>
        
        <!-- Clock and Calendar Section -->
        <div class="header-clock-calendar">
            <div class="clock-display">
                <i class="fas fa-clock"></i>
                <span id="current-time">--:--:--</span>
            </div>
            <div class="calendar-display">
                <i class="fas fa-calendar"></i>
                <span id="current-date">--/--/----</span>
            </div>
        </div>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <button class="admin-btn" onclick="window.location.href='admin_dashboard.html'">Admin Panel</button>
        <?php endif; ?>
        <div class="navbar">
            <div class="navlist">
                <button onclick="window.location.href='pet_adoption_feed.html'">pet adoption</button>
                <button onclick="window.location.href='vet_appointment.html'">vet appointment</button>
                <button onclick="window.location.href='pet_corner.html'">pet corner</button>
                <button onclick="window.location.href='pet_community.html'">community</button>
                <button onclick="window.location.href='shop_feed.html'">shop</button>
                <button onclick="window.location.href='customer_support.html'">Customer Support</button>
                <button class="subscription-nav-btn" onclick="window.location.href='subscription_plans.html'">Subscription</button>
            </div>
        </div>
    </header>
    <div class="kitten-bg-wrapper">
        <img src="images/kitten.png" alt="kitten" class="kitten-photo">
    </div>
    <div class="text-container">
        <h1>Welcome to PawConnect!</h1>
        <h4>Your one-stop solution for all your pet needs.</h4>
        <button class="get-started" onclick="window.location.href='login.html'">Get Started</button>
    </div>

    <!-- Quick Access Buttons Section -->
    <div class="quick-access-section">
        <button class="quick-btn" onclick="window.location.href='pet_adoption_feed.html'"><i class="fas fa-paw"></i> Adopt a Pet</button>
        <button class="quick-btn" onclick="window.location.href='vet_appointment.html'"><i class="fas fa-user-md"></i> Book a Vet</button>
        <button class="quick-btn" onclick="window.location.href='shop_feed.html'"><i class="fas fa-shopping-cart"></i> Shop Now</button>
        <button class="quick-btn" onclick="window.location.href='pet_community.html'"><i class="fas fa-users"></i> Join Community</button>
        <button class="quick-btn" onclick="window.location.href='customer_support.html'"><i class="fas fa-headset"></i> Customer Support</button>
    </div>

    <div class="pets">
        <h1 id="head_pet_adptt">Pets Availabe for Adoption Near You</h1>
        <button class="seemore" onclick="window.location.href='pet_adoption_feed.html'">See more</button>

        <div class="petbanners">
            <img src="images/kitten_two.png" alt="pet_photo" id="citto">
            <p>citto</p>
            <button>Learn More</button>
        </div>
        <div class="petbanners">
            <img src="images/kitten_one.png" alt="pet_photo" id="ginger">
            <p>ginger</p>
            <button>Learn More</button>
        </div>
        <div class="petbanners">
            <img src="images/doggo.png" alt="pet_photo" id="orange">
            <p>orange</p>
            <button>Learn More</button>
        </div>
    </div>
    <h1 id="heading3rdpage">HOW TO ADOPT A PET</h1>

    <div class="pets">
      <div class="adoptedpets">
       <img src="images/adopt_pets.png" alt="pet_photo">
       <div class="adoptlists">
          <p>&#9673; <br> find the pet</p>
          <p>&#9673; <br> meet the pet owner </p>
          <p>&#9673; <br> fill up the form</p>
          <p>&#9673; <br> and adopt the pet yay!</p>
       </div>
      </div>

      <div class="adopt-texts">
        <h1>Adopt Pets And <br> Save Their Lives</h1> 
        <p>Why bother shopping for pets when they are thousands of homeless puppies
          and kittens looking for afamily? Adopt rescued animals from our shelters
          and make a change in the lives of animals in your area.
        </p>
      </div>
    </div>

    <!-- Vet Appointment Section -->
    <h1 id="heading3rdpage">FIND A VETERINARIAN</h1>
    <div class="pets">
      <div class="adoptedpets">
        <img src="images/allpet.png" alt="vet_photo">
        <div class="adoptlists">
          <p>&#9673; <br> choose location</p>
          <p>&#9673; <br> select specialist</p>
          <p>&#9673; <br> pick date & time</p>
          <p>&#9673; <br> book appointment</p>
        </div>
      </div>

      <div class="adopt-texts">
        <h1>Expert Veterinary Care <br> At Your Service</h1> 
        <p>Connect with certified veterinarians across Bangladesh. From routine check-ups to emergency care,
          our network of experienced vets ensures your pet receives the best possible care.
          Book appointments easily and get professional medical attention for your furry friends.
        </p>
        <button class="seemore" onclick="window.location.href='vet_appointment.html'">Book Now</button>
      </div>
    </div>

    <!-- Pet Corner Section -->
    <h1 id="heading3rdpage">PET CORNER</h1>
    <div class="pets">
      <div class="adoptedpets">
        <img src="images/pet_photos.png" alt="pet_corner_photo">
        <div class="adoptlists">
          <p>&#9673; <br> share photos</p>
          <p>&#9673; <br> post updates</p>
          <p>&#9673; <br> get likes</p>
          <p>&#9673; <br> connect with others</p>
        </div>
      </div>

      <div class="adopt-texts">
        <h1>Share Your Pet's <br> Special Moments</h1> 
        <p>Create a profile for your pet and share their daily adventures, milestones, and cute moments.
          Get likes and comments from other pet lovers, and build a community around your furry friend's journey.
        </p>
        <button class="seemore" onclick="window.location.href='pet_corner.html'">Join Pet Corner</button>
      </div>
    </div>

    <!-- Pet Corner Features Section -->
    <div class="petcorner-section">
        <h2>Explore Pet Corner</h2>
        <div class="petcorner-features">
            <div class="petcorner-card">
                <i class="fas fa-id-badge"></i>
                <h3>Create Profile</h3>
                <p>Showcase your pet's personality, photos, and fun facts.</p>
                <button onclick="window.location.href='pet_corner.html'">Create Profile</button>
            </div>
            <div class="petcorner-card">
                <i class="fas fa-camera-retro"></i>
                <h3>Share Photo</h3>
                <p>Upload and share your pet's cutest moments with the community.</p>
                <button onclick="window.location.href='pet_corner.html'">Share Photo</button>
            </div>
            <div class="petcorner-card">
                <i class="fas fa-book-open"></i>
                <h3>Pet Stories</h3>
                <p>Write and read stories about your pet's adventures and milestones.</p>
                <button onclick="window.location.href='pet_corner.html'">Read Stories</button>
            </div>
            <div class="petcorner-card">
                <i class="fas fa-images"></i>
                <h3>Gallery</h3>
                <p>Browse a gallery of all shared pet photos and moments.</p>
                <button onclick="window.location.href='pet_corner.html'">View Gallery</button>
            </div>
        </div>
    </div>

    <!-- Community Section -->
    <h1 id="heading3rdpage">PET COMMUNITY</h1>
    <div class="pets">
      <div class="adoptedpets">
        <img src="images/login_pets.png" alt="community_photo">
        <div class="adoptlists">
          <p>&#9673; <br> join groups</p>
          <p>&#9673; <br> share experiences</p>
          <p>&#9673; <br> get advice</p>
          <p>&#9673; <br> make friends</p>
        </div>
      </div>

      <div class="adopt-texts">
        <h1>Connect With Fellow <br> Pet Enthusiasts</h1> 
        <p>Join our vibrant community of pet lovers. Share experiences, get advice from experienced pet owners,
          participate in events, and make lasting connections with people who share your passion for pets.
        </p>
        <button class="seemore" onclick="window.location.href='pet_community.html'">Join Community</button>
      </div>
    </div>

    <!-- Community Features Section -->
    <div class="community-section">
        <h2>Join the PawConnect Community</h2>
        <div class="community-features">
            <div class="community-card">
                <i class="fas fa-comments"></i>
                <h3>Forums</h3>
                <p>Discuss pet care, adoption, health, and more with fellow pet lovers.</p>
                <button onclick="window.location.href='pet_community.html'">View Forums</button>
            </div>
            <div class="community-card">
                <i class="fas fa-users"></i>
                <h3>Groups</h3>
                <p>Join or create groups based on pet type, breed, or interests.</p>
                <button onclick="window.location.href='pet_community.html'">Explore Groups</button>
            </div>
            <div class="community-card">
                <i class="fas fa-calendar-alt"></i>
                <h3>Events</h3>
                <p>Stay updated on upcoming meetups, adoption drives, and webinars.</p>
                <button onclick="window.location.href='pet_community.html'">See Events</button>
            </div>
            <div class="community-card">
                <i class="fas fa-question-circle"></i>
                <h3>Q&amp;A</h3>
                <p>Ask questions and get advice from experienced pet owners and experts.</p>
                <button onclick="window.location.href='pet_community.html'">Ask a Question</button>
            </div>
        </div>
    </div>

    <!-- Subscription Plans Section -->
    <h1 id="heading3rdpage">CHOOSE YOUR SUBSCRIPTION PLAN</h1>
    <div class="subscription-home-section">
        <div class="subscription-intro">
            <h2>Unlock Premium Features</h2>
            <p>Get the most out of PawConnect with our exclusive subscription plans designed for every pet lover.</p>
        </div>
        <div class="subscription-plans-home">
            <!-- Bronze Plan -->
            <div class="plan-card-home bronze">
                <div class="plan-badge-home">BRONZE</div>
                <div class="plan-name-home">Bronze</div>
                <div class="plan-price-home">৳299<small>/month</small></div>
                <ul class="plan-features-home">
                    <li><i class="fas fa-check"></i> Basic pet adoption access</li>
                    <li><i class="fas fa-check"></i> Standard vet appointment booking</li>
                    <li><i class="fas fa-check"></i> Community forum access</li>
                    <li><i class="fas fa-check"></i> Basic pet corner features</li>
                    <li><i class="fas fa-check"></i> Email support</li>
                </ul>
                <button class="subscribe-btn-home" onclick="window.location.href='subscription_plans.html'">Get Started</button>
            </div>

            <!-- Silver Plan -->
            <div class="plan-card-home silver">
                <div class="plan-badge-home">SILVER</div>
                <div class="plan-name-home">Silver</div>
                <div class="plan-price-home">৳599<small>/month</small></div>
                <ul class="plan-features-home">
                    <li><i class="fas fa-check"></i> All Bronze features</li>
                    <li><i class="fas fa-check"></i> Priority pet adoption listings</li>
                    <li><i class="fas fa-check"></i> Fast-track vet appointments</li>
                    <li><i class="fas fa-check"></i> Advanced pet corner features</li>
                    <li><i class="fas fa-check"></i> Priority customer support</li>
                    <li><i class="fas fa-check"></i> 50% off shop items</li>
                </ul>
                <button class="subscribe-btn-home" onclick="window.location.href='subscription_plans.html'">Get Started</button>
            </div>

            <!-- Gold Plan -->
            <div class="plan-card-home gold">
                <div class="plan-badge-home">GOLD</div>
                <div class="plan-name-home">Gold</div>
                <div class="plan-price-home">৳999<small>/month</small></div>
                <ul class="plan-features-home">
                    <li><i class="fas fa-check"></i> All Silver features</li>
                    <li><i class="fas fa-check"></i> VIP pet adoption priority</li>
                    <li><i class="fas fa-check"></i> 24/7 premium vet support</li>
                    <li><i class="fas fa-check"></i> Unlimited pet corner posts</li>
                    <li><i class="fas fa-check"></i> Dedicated support manager</li>
                    <li><i class="fas fa-check"></i> Free shop delivery</li>
                    <li><i class="fas fa-check"></i> No advertisements</li>
                </ul>
                <button class="subscribe-btn-home" onclick="window.location.href='subscription_plans.html'">Get Started</button>
            </div>
        </div>
    </div>

    <!-- Shop Section -->
    <h1 id="heading3rdpage">PET SHOP</h1>
    <div class="pets">
      <div class="adoptedpets">
        <img src="images/cat_food.jpg" alt="shop_photo">
        <div class="adoptlists">
          <p>&#9673; <br> browse products</p>
          <p>&#9673; <br> add to cart</p>
          <p>&#9673; <br> secure payment</p>
          <p>&#9673; <br> fast delivery</p>
        </div>
      </div>

      <div class="adopt-texts">
        <h1>Everything Your Pet <br> Needs in One Place</h1> 
        <p>Discover a wide range of high-quality pet products. From premium food and treats to toys,
          accessories, and grooming supplies. Shop with confidence knowing all products are carefully
          selected for your pet's well-being.
        </p>
        <button class="seemore" onclick="window.location.href='shop_feed.html'">Shop Now</button>
      </div>
    </div>

    <div class="container">
      <div class="sidebar">
        <h1>Stories</h1>
        <div class="indicator">
          <div class="circle"></div>
          <div class="line"></div>
        </div>
        <div class="pagination">
          <span class="active" onclick="scrollToCard(0)">01</span>
          <span onclick="scrollToCard(1)">02</span>
          <span onclick="scrollToCard(2)">03</span>
          <span onclick="scrollToCard(3)">04</span>
          <span onclick="scrollToCard(4)">05</span>
        </div>
      </div>
  
      <div class="cards" id="cards">
        <div class="card">
          <h2>01/</h2>
          <h2>Find Trusted Pet Services</h2>
          <p>PawConnect made it super easy to find a reliable groomer for my dog.
            The reviews were honest, booking was fast, and my pup looked amazing after his appointment</p>
          <div class="shape red"></div>
          <a href="#">View Details →</a>
        </div>
        <div class="card">
          <h2>02/</h2>
          <h2>Book a Pet Sitter Instantly</h2>
          <p>I was nervous about leaving my cat while traveling, but PawConnect helped me find a sitter I could trust.
            Updates, photos, and total peace of mind the whole trip!</p>
          <div class="shape green"></div>
          <a href="#">View Details →</a>
        </div>
        <div class="card">
          <h2>03/</h2>
          <h3>Join the PawConnect Community</h3>
          <p>I love the PawConnect community! It's amazing to connect with other pet lovers, swap tips, and share stories. 
            Everyone is super supportive — it feels like a big family for pets!</p>
          <div class="shape pink"></div>
          <a href="#">View Details →</a>
        </div>
        <div class="card">
          <h2>04/</h2>
          <h2>Connect With Vets & Trainers</h2>
          <p>I booked a dog trainer through PawConnect and it changed everything.
            My dog listens better, and the trainer was certified and super friendly. Highly recommend!</p>
          <div class="shape blue"></div>
          <a href="#">View Details →</a>
        </div>
        <div class="card">
          <h2>05/</h2>
          <h2>Shop for Your Pets</h2>
          <p>Shopping on PawConnect is a game-changer!
             I found everything from healthy treats to stylish collars — and the delivery was super fast.
              My dog's new favorite toy came the next day!</p>
          <div class="shape yellow"></div>
          <a href="#">View Details →</a>
        </div>
      </div>
    </div>
  
  <h1 id=review-head>See Reviews and enjoy the first ever pet platform of Bangladesh
<div class="review-section">
  <div id="review-text">
    <strong>PawConnect</strong>
    <h5>Country's first pet platform to connect all the pet enthusiasts.</h5>
  </div>
  <div class="reviews-wrapper" id="reviewsWrapper">
    
    <div class="review-card">
      <div class="review-image" style="background-image: url('images/kitten_one.png');"></div>
      <div class="review-content">
        <div class="review-header">
          <div>
            <h2>Sahir</h2>
            <p class="date">01/04/2025</p>
          </div>
          <div class="review-rating">
            <p>5.0</p>
            <div class="stars">★★★★★</div>
          </div>
        </div>
        <p class="review-text">
          PawConnect made it super easy to find healthy food and cute toys for my kitten. Fast delivery, great service!
        </p>
      </div>
    </div>

    <div class="review-card">
      <div class="review-image" style="background-image: url('images/review_dogg.png');"></div>
      <div class="review-content">
        <div class="review-header">
          <div>
            <h2>Nabil</h2>
            <p class="date">26/03/2025</p>
          </div>
          <div class="review-rating">
            <p>4.8</p>
            <div class="stars">★★★★☆</div>
          </div>
        </div>
        <p class="review-text">
          Booking a vet visit was so easy through PawConnect. No stress, just a few clicks!
        </p>
      </div>
    </div>

    <div class="review-card">
      <div class="review-image" style="background-image: url('images/doge_one.png');"></div>
      <div class="review-content">
        <div class="review-header">
          <div>
            <h2>Sabbir</h2>
            <p class="date">01/01/2025</p>
          </div>
          <div class="review-rating">
            <p>5.0</p>
            <div class="stars">★★★★★</div>
          </div>
        </div>
        <p class="review-text">
          PawConnect optimized the solution for pet food and it was super easy to order and also i got grooming toys for my puppies.
        </p>
      </div>
    </div>

    <div class="review-card">
      <div class="review-image" style="background-image: url('images/cool_cat.png');"></div>
      <div class="review-content">
        <div class="review-header">
          <div>
            <h2>Samiha</h2>
            <p class="date">21/04/2025</p>
          </div>
          <div class="review-rating">
            <p>4.8</p>
            <div class="stars">★★★★☆</div>
          </div>
        </div>
        <p class="review-text">
          I am loving the community platform. It is a unique feature and i can share my pet photos with other.
        </p>
      </div>
    </div>

    <div class="review-card">
      <div class="review-image" style="background-image: url('images/bird_kool.png');"></div>
      <div class="review-content">
        <div class="review-header">
          <div>
            <h2>Nusaiba</h2>
            <p class="date">17/04/2025</p>
          </div>
          <div class="review-rating">
            <p>5.0</p>
            <div class="stars">★★★★★</div>
          </div>
        </div>
        <p class="review-text">
          PawConnect made it super easy to find healthy food and cute toys for my kitten. Fast delivery, great service!
        </p>
      </div>
    </div>

  </div>
</div>  

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Us</h3>
                <p>PawConnect is your one-stop platform for pet adoption, veterinary services, and pet care products. We connect loving homes with pets in need.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="home.php">Home</a></li>
                    <li><a href="pet_adoption_feed.html">Adopt a Pet</a></li>
                    <li><a href="vet_appointment.html">Vet Appointments</a></li>
                    <li><a href="pet_corner.html">Pet Corner</a></li>
                    <li><a href="pet_community.html">Community</a></li>
                    <li><a href="shop_feed.html">Pet Shop</a></li>
                    <li><a href="customer_support.html">Customer Support</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Services</h3>
                <ul>
                    <li><a href="pet_adoption_feed.html">Pet Adoption</a></li>
                    <li><a href="vet_appointment.html">Veterinary Care</a></li>
                    <li><a href="shop_feed.html">Pet Supplies</a></li>
                    <li><a href="customer_support.html">Pet Care Tips</a></li>
                    <li><a href="customer_support.html">Emergency Support</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <p>Follow us on social media for updates and pet care tips.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/" target="_blank"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 PawConnect. All rights reserved.</p>
        </div>
    </footer>

    <script src="javascripts/home.js"></script>
    
    <!-- Clock and Calendar JavaScript -->
    <script>
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }
        
        function updateDate() {
            const now = new Date();
            const dateString = now.toLocaleDateString('en-US', {
                month: '2-digit',
                day: '2-digit',
                year: 'numeric'
            });
            document.getElementById('current-date').textContent = dateString;
        }
        
        // Update clock and date immediately
        updateClock();
        updateDate();
        
        // Update clock every second
        setInterval(updateClock, 1000);
        
        // Update date every minute (in case of date change)
        setInterval(updateDate, 60000);
    </script>
</body>
</html> 
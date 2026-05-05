// ==========================
// UKLOOLE LEARNING HUB
// index.js — Shared JS
// ==========================

// ---- MOBILE MENU ----
const hamburger = document.getElementById("hamburger");
const mobileMenu = document.getElementById("mobile-menu");

if (hamburger && mobileMenu) {
  hamburger.addEventListener("click", function () {
    mobileMenu.classList.toggle("open");
    const isOpen = mobileMenu.classList.contains("open");
    hamburger.setAttribute("aria-expanded", isOpen);
  });

  document.addEventListener("click", function (e) {
    if (!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) {
      mobileMenu.classList.remove("open");
    }
  });
}

// ---- CART ----
let cart = [];
const PAYSTACK_KEY = "pk_live_b7249de7d74fe255f72dc04cfcb8495f22f06353";

function updateCartUI() {
  const cartCount = document.getElementById("cart-count");
  const cartItems = document.getElementById("cart-items-list");
  const cartEmptyMsg = document.getElementById("cart-empty");
  const cartFooter = document.getElementById("cart-footer");
  const cartTotal = document.getElementById("cart-total");
  const checkoutBtn = document.getElementById("checkout-btn");

  if (!cartCount) return;

  const totalItems = cart.reduce((sum, i) => sum + i.quantity, 0);
  cartCount.textContent = totalItems;
  cartCount.style.display = totalItems > 0 ? "flex" : "none";

  if (cartItems) {
    cartItems.innerHTML = "";
    cart.forEach((item) => {
      const li = document.createElement("div");
      li.className = "cart-item";
      li.innerHTML = `
        <div class="cart-item-info">
          <h4>${item.name}</h4>
          <p>₦${item.price.toLocaleString("en-NG")}</p>
        </div>
        <button class="cart-item-remove" onclick="removeFromCart('${item.id}')" title="Remove">🗑</button>
      `;
      cartItems.appendChild(li);
    });
  }

  if (cartEmptyMsg) cartEmptyMsg.style.display = cart.length === 0 ? "flex" : "none";
  if (cartFooter) cartFooter.style.display = cart.length > 0 ? "block" : "none";

  if (cartTotal) {
    const total = cart.reduce((sum, i) => sum + i.price * i.quantity, 0);
    cartTotal.textContent = "₦" + total.toLocaleString("en-NG");
  }
}

function addToCart(id, name, price) {
  const existing = cart.find((i) => i.id === id);
  if (existing) {
    existing.quantity += 1;
  } else {
    cart.push({ id, name, price, quantity: 1 });
  }

  const btn = document.getElementById("cart-btn-" + id);
  if (btn) {
    btn.innerHTML = "✓ Added";
    btn.classList.add("added");
    btn.disabled = true;
  }

  updateCartUI();
  toggleCart(true);
}

function removeFromCart(id) {
  cart = cart.filter((i) => i.id !== id);
  const btn = document.getElementById("cart-btn-" + id);
  if (btn) {
    btn.innerHTML = "🛒 Add to Cart";
    btn.classList.remove("added");
    btn.disabled = false;
  }
  updateCartUI();
  resetCheckoutForm();
}

function toggleCart(forceOpen) {
  const overlay = document.getElementById("cart-overlay");
  const panel = document.getElementById("cart-panel");
  if (!overlay || !panel) return;

  const willOpen = forceOpen !== undefined ? forceOpen : !panel.classList.contains("open");
  overlay.classList.toggle("open", willOpen);
  panel.classList.toggle("open", willOpen);
  document.body.style.overflow = willOpen ? "hidden" : "";
  updateCartUI();
}

function proceedToCheckout() {
  const form = document.getElementById("checkout-form");
  const checkoutBtn = document.getElementById("checkout-btn");
  if (!form) return;
  if (!form.classList.contains("show")) {
    form.classList.add("show");
    if (checkoutBtn) checkoutBtn.textContent = "Pay with Paystack";
  } else {
    startCartPayment();
  }
}

function startCartPayment() {
  const name = document.getElementById("checkout-name");
  const email = document.getElementById("checkout-email");
  if (!name || !email || !name.value || !email.value) {
    alert("Please enter your name and email to continue.");
    return;
  }

  const total = cart.reduce((sum, i) => sum + i.price * i.quantity, 0);
  const cartSummary = cart.map((i) => i.name + " x" + i.quantity).join(", ");
  initiatePaystack(email.value, name.value, total * 100, "materials", { cart_summary: cartSummary }, function (reference) {
    // Call save-order.php to record the purchase, generate a token and email the download link
    fetch("save-order.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        name:      name.value,
        email:     email.value,
        reference: reference,
        amount:    total,
        items:     cart.map(function(i) { return { name: i.name, qty: i.quantity }; })
      })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        alert("Payment successful! \u2705\nYour download link has been sent to " + email.value + ".\nPlease check your inbox (and spam folder).");
      } else {
        alert("Payment received but we could not send your download link. Please contact learn@ukloole.com with your reference: " + reference);
      }
    })
    .catch(function() {
      alert("Payment received! Please contact learn@ukloole.com with your reference: " + reference + " to get your download link.");
    });
    cart = [];
    updateCartUI();
    toggleCart(false);
    resetCheckoutForm();
  });
}

function resetCheckoutForm() {
  const form = document.getElementById("checkout-form");
  const checkoutBtn = document.getElementById("checkout-btn");
  if (form) form.classList.remove("show");
  if (checkoutBtn) checkoutBtn.textContent = "Checkout Securely";
}

// ---- PAYSTACK ----
// purchaseType: "materials" or "community" — used to route the unified webhook
// extraMeta:    optional object merged into Paystack metadata
function initiatePaystack(email, name, amountKobo, purchaseType, extraMeta, onSuccess) {
  const prefix = purchaseType === "community" ? "lh_com_" : "lh_mat_";
  const ref = prefix + Date.now() + "_" + Math.floor(Math.random() * 1e6);

  const metadata = Object.assign(
    {
      app: "learning_hub",
      purchase_type: purchaseType,
      name: name,
      custom_fields: [
        { display_name: "Full Name", variable_name: "full_name", value: name },
        { display_name: "App",       variable_name: "app",       value: "Learning Hub" },
        { display_name: "Type",      variable_name: "type",      value: purchaseType },
      ],
    },
    extraMeta || {}
  );

  const handler = PaystackPop.setup({
    key: PAYSTACK_KEY,
    email: email,
    amount: amountKobo,
    currency: "NGN",
    ref: ref,
    channels: ["card", "bank", "ussd", "qr", "mobile_money", "bank_transfer", "eft"],
    metadata: metadata,
    callback: function (response) {
      onSuccess(response.reference);
    },
    onClose: function () {
      console.log("Paystack closed.");
    },
  });
  handler.openIframe();
}

// ---- COMMUNITY PAGE ----
function payWithPaystack() {
  const name = document.getElementById("community-name");
  const email = document.getElementById("community-email");
  if (!name || !email || !name.value || !email.value) {
    alert("Please fill in your name and email.");
    return;
  }
  initiatePaystack(email.value, name.value, 2500000, "community", {}, function (reference) {
    alert("Welcome to Premium! Reference: " + reference + "\nCheck your email for setup instructions.");
    name.value = "";
    email.value = "";
  });
}

function sendAssessment() {
  const name = document.getElementById("assess-name");
  const email = document.getElementById("assess-email");
  if (!name || !email || !name.value || !email.value) {
    alert("Please fill in your name and email.");
    return;
  }

  fetch("send-assessment.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "name=" + encodeURIComponent(name.value) + "&email=" + encodeURIComponent(email.value),
  })
    .then((r) => r.text())
    .then((data) => {
      if (data.trim() === "success") {
        alert("Assessment link sent! Please check your email.");
        closeModal("assess-modal");
      } else {
        alert("Could not send email. Please try again.");
      }
    })
    .catch(() => alert("Network error. Please try again."));
}

// ---- MODAL ----
function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add("open");
    document.body.style.overflow = "hidden";
  }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove("open");
    document.body.style.overflow = "";
  }
}

// ---- COURSES: QUIZ & SCENARIO ----
document.addEventListener("DOMContentLoaded", function () {
  // Lesson accordion
  document.querySelectorAll(".lesson-header").forEach(function (header) {
    header.addEventListener("click", function () {
      const body = header.nextElementSibling;
      const chevron = header.querySelector(".lesson-chevron");
      const isOpen = body.classList.contains("open");
      body.classList.toggle("open", !isOpen);
      if (chevron) chevron.classList.toggle("open", !isOpen);
    });
  });

  // Quiz collapsible
  document.querySelectorAll(".collapsible-trigger").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const body = btn.nextElementSibling;
      body.classList.toggle("open");
    });
  });

  // Quiz submit
  document.querySelectorAll(".quiz-submit").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const box = btn.closest(".quiz-box");
      const selected = box.querySelector("input[type=radio]:checked");
      const feedback = box.querySelector(".quiz-feedback");
      if (!selected) {
        feedback.textContent = "⚠ Please select an answer.";
        feedback.className = "quiz-feedback";
        return;
      }
      const isCorrect = selected.dataset.correct === "true";
      feedback.textContent = isCorrect ? "✅ Correct! Well done." : "❌ Incorrect. Try again.";
      feedback.className = "quiz-feedback " + (isCorrect ? "feedback-correct" : "feedback-wrong");
      if (isCorrect) {
        box.dataset.passed = "true";
        checkLessonCompletion(box.closest(".lesson-body"));
      }
    });
  });

  // Scenario buttons
  document.querySelectorAll(".scenario-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const box = btn.closest(".scenario-box");
      const feedback = box.querySelector(".quiz-feedback");
      const isCorrect = btn.dataset.correct === "true";
      feedback.textContent = isCorrect ? "✅ Great choice!" : "❌ Not the best choice. Try again.";
      feedback.className = "quiz-feedback " + (isCorrect ? "feedback-correct" : "feedback-wrong");
      if (isCorrect) {
        box.dataset.passed = "true";
        checkLessonCompletion(box.closest(".lesson-body"));
      }
    });
  });

  function checkLessonCompletion(lessonBody) {
    if (!lessonBody) return;
    const quiz = lessonBody.querySelector(".quiz-box");
    const scenario = lessonBody.querySelector(".scenario-box");
    if (quiz && quiz.dataset.passed !== "true") return;
    if (scenario && scenario.dataset.passed !== "true") return;
    if (!lessonBody.querySelector(".continue-btn")) {
      const btn = document.createElement("button");
      btn.className = "continue-btn";
      btn.innerHTML = "Continue ➡";
      lessonBody.appendChild(btn);
    }
  }

  // Mark active nav links
  const path = window.location.pathname.split("/").pop() || "index.html";
  document.querySelectorAll(".nav-links a, .mobile-menu a").forEach(function (a) {
    if (a.getAttribute("href") === path) {
      a.classList.add("active");
    }
  });
});
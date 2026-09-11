document.addEventListener("DOMContentLoaded", function () {
    const loginOverlay = document.getElementById("loginOverlay");
    const closeLogin = document.getElementById("closeLogin");
    const password = document.getElementById("password");
    const showPassword = document.getElementById("showPassword");
    const loginTriggers = document.querySelectorAll(".requires-login");

    function openLogin(event) {
        event.preventDefault();

        if (!loginOverlay) return;

        loginOverlay.classList.add("show");
        loginOverlay.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");

        setTimeout(function () {
            document.getElementById("email")?.focus();
        }, 50);
    }

    function closeLoginModal() {
        if (!loginOverlay) return;

        loginOverlay.classList.remove("show");
        loginOverlay.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");
    }

    loginTriggers.forEach(function (element) {
        element.addEventListener("click", openLogin);
    });

    if (closeLogin) {
        closeLogin.addEventListener("click", closeLoginModal);
    }

    if (loginOverlay) {
        loginOverlay.addEventListener("click", function (event) {
            if (event.target === loginOverlay) {
                closeLoginModal();
            }
        });
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeLoginModal();
        }
    });

    if (showPassword && password) {
        showPassword.addEventListener("click", function () {
            const isPassword = password.type === "password";

            password.type = isPassword ? "text" : "password";
            showPassword.textContent = isPassword ? "HIDE" : "SHOW";
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const cartButtons = document.querySelectorAll(".add-cart-button");
    const cartPopup = document.getElementById("cartPopup");
    const cartPopupClose = document.getElementById("cartPopupClose");
    const cartContinue = document.getElementById("cartContinue");
    const cartPopupMessage = document.getElementById("cartPopupMessage");

    cartButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const productId = this.dataset.productId;

            if (!productId) {
                alert("Invalid product.");
                return;
            }

            const formData = new FormData();
            formData.append("product_id", productId);

            fetch("actions/add-to-cart.php", {
                method: "POST",
                body: formData
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error("Server error: " + response.status);
                    }

                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        if (data.logged_in === false) {
                            window.location.href = "login.php?redirect=products.php";
                            return;
                        }

                        alert(data.message || "Unable to add the product to your cart.");
                        return;
                    }

                    const cartNumber = document.querySelector(".cart-number");

                    if (cartNumber) {
                        cartNumber.textContent = data.cart_count;
                    }

                    if (cartPopupMessage) {
                        cartPopupMessage.textContent =
                            data.product_name + " was added to your cart.";
                    }

                    if (cartPopup) {
                        cartPopup.style.display = "flex";
                    }
                })
                .catch(function (error) {
                    console.error("Add to cart error:", error);
                    alert("Something went wrong while adding the product.");
                });
        });
    });

    if (cartPopupClose) {
        cartPopupClose.addEventListener("click", function () {
            if (cartPopup) {
                cartPopup.style.display = "none";
            }
        });
    }

    if (cartContinue) {
        cartContinue.addEventListener("click", function () {
            if (cartPopup) {
                cartPopup.style.display = "none";
            }
        });
    }

    if (cartPopup) {
        cartPopup.addEventListener("click", function (event) {
            if (event.target === cartPopup) {
                cartPopup.style.display = "none";
            }
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const logoutOverlay = document.getElementById("logoutOverlay");
    const logoutClose = document.getElementById("logoutClose");
    const logoutCancel = document.getElementById("logoutCancel");

    document.addEventListener("click", function (event) {
        const logoutButton = event.target.closest(".logout-button");

        if (!logoutButton) return;

        event.preventDefault();

        if (!logoutOverlay) {
            console.error("Logout popup was not found.");
            return;
        }

        logoutOverlay.classList.add("show");
        logoutOverlay.setAttribute("aria-hidden", "false");
    });

    if (logoutClose) {
        logoutClose.addEventListener("click", function () {
            logoutOverlay.classList.remove("show");
            logoutOverlay.setAttribute("aria-hidden", "true");
        });
    }

    if (logoutCancel) {
        logoutCancel.addEventListener("click", function () {
            logoutOverlay.classList.remove("show");
            logoutOverlay.setAttribute("aria-hidden", "true");
        });
    }

    if (logoutOverlay) {
        logoutOverlay.addEventListener("click", function (event) {
            if (event.target === logoutOverlay) {
                logoutOverlay.classList.remove("show");
                logoutOverlay.setAttribute("aria-hidden", "true");
            }
        });
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && logoutOverlay) {
            logoutOverlay.classList.remove("show");
            logoutOverlay.setAttribute("aria-hidden", "true");
        }
    });
});
//Password Reveal
const revealBtn = document.querySelector(".reveal");
const passwordInput = document.getElementById("password");

if (revealBtn && passwordInput) {
    revealBtn.addEventListener("click", function () {
        passwordInput.type = passwordInput.type === "password" ? "text" : "password"; //Ternary operator for revealing
    });
}

//Login Validation
function validateLogin(event) {
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();

    if (!email || !password) {
        alert("Please fill in both email and password.");
        event.preventDefault();
        return false;
    }
    return true;
}

//Register validation
async function validateRegister(event) {
    const email = document.getElementById("email").value.trim();
    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();
    const confirm = document.getElementById("confirm").value.trim();

    if (!email || !username || !password || !confirm) { //Checks empty fields
        alert("Please fill out all fields.");
        event.preventDefault();
        return false;
    }

    if (password.length < 8) { //Checks password length
        alert("Password must be at least 8 characters long.");
        event.preventDefault();
        return false;
    }

    if (password !== confirm) { //Checks if confirm same as password
        alert("Passwords do not match.");
        event.preventDefault();
        return false;
    }

    // Validate email format (AI SUGGESTED)
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) { 
        alert("Please enter a valid email address."); //If doesnt follow emailRegex format
        event.preventDefault();
        return false;
    }

    // AJAX email check before submitting
    try {
        const exists = await checkEmailExists(email);

        if (exists) { //If email exists or doesnt exist based on function
            alert("An account with that email already exists."); 
            event.preventDefault();
            return false;
        }
    } catch (err) {
        console.error("Email check failed:", err);
    }

    return true;
}

//Checks email availability via AJAX
async function checkEmailExists(email) { //async to check email availability
    const params = new URLSearchParams();
    params.append("email", email);

    const resp = await fetch("register.php", { //sends POST request to register.php
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Check-Email": "true"
        },
        body: params.toString()
    });

    if (!resp.ok) return false; //Email doesnt exist

    const data = await resp.json();
    return data.exists === true; //Email exists
}

document.addEventListener("DOMContentLoaded", function () {
    const registerForm = document.querySelector('form[action="register.php"]');
    if (registerForm) {
        registerForm.addEventListener("submit", async function (e) {
            const ok = await validateRegister(e);
            if (!ok) e.preventDefault();
        });
    }

    const loginForm = document.querySelector('form[action="login.php"]');
    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            const ok = validateLogin(e);
            if (!ok) e.preventDefault();
        });
    }
});

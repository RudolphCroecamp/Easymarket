import {BACKEND_URL} from "../config.js"



document.addEventListener("DOMContentLoaded", ()=>{
    const signupForm = document.getElementById("signupForm");

    signupForm.addEventListener("submit", (e) => {
        e.preventDefault()
        hideErrorMessage()

        console.log("requesting");
        const fname = document.getElementById("fname")
        const lname = document.getElementById("lname")
        const email = document.getElementById("email")
        const password = document.getElementById("password")
        const conpassword = document.getElementById("conpassword")

        console.log(validateInput(fname, lname, email, password, conpassword));
        console.log(validateEmail(email));

        if(
            validateInput(fname, lname, email, password, conpassword) 
            && 
            validateEmail(email.value)
        )
        {
            //get data
            const formData = new FormData(signupForm)

            console.log(formData);

            //submit inputs to backend after fields have been validated
            fetch(`${BACKEND_URL}/Auth/signup.php`,
                {
                    method : "POST",
                    body : formData
                }
            )
            .then(res => res.json())
            .then(data =>{
                console.log(data);

                if(data.success === true){
                    window.location = "/src/pages/auth/login.html"
                }else{
                    setErrorMessage(data.error)
                }
            })

        }
    });
})


//validate an array of inputs
function validateInput(...elements){
    for (let elm of elements) {
        if(!elm.value || elm.value.trim().length === 0){
            elm.focus()
            setErrorMessage("Fill in all fields");
            return false;
        }
    }
    return true;
}

//validate an email address
function validateEmail(email) {
    console.log(email);
    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    if(!emailPattern.test(email)){
        document.getElementById("email").focus();
        setErrorMessage("Invalid email address");
        return false;
    }

    return true;
}


//show error message to client
function setErrorMessage(error){
    document.getElementById("error-box").classList.remove("visually-hidden");
    document.getElementById("error-message").innerText = error;
}

//hide error message from client
function hideErrorMessage(){
    document.getElementById("error-box").classList.add("visually-hidden");
    document.getElementById("error-message").innerText = "";
}
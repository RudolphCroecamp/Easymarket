
import Cart from "../cart/Cart.js";
import {BACKEND_URL, IMAGES_URL} from "../config.js"

import { searchAddresses } from "../locationServices.js";

//handle errors
import {showToast} from "../toast.js"
import {setErrorMessage, hideErrorMessage} from "../handleErrorMessage.js"

document.addEventListener("DOMContentLoaded", async ()=>{
    try {
        const userInfo = await getUserInfo()

        if(userInfo.success == true){
            const user = userInfo.user

            document.getElementById("fName").value = user.fName
            document.getElementById("lName").value = user.lName 
            document.getElementById("email").value = user.email
        }


        await renderCartItems()


        let debounceTimer;
        document.getElementById("userStreet").addEventListener("input", (e) => {
            clearTimeout(debounceTimer);

            const query = e.target.value;

            debounceTimer = setTimeout(async () => {
                const results = await searchAddresses(query);
                renderAddressDropdown(results);
            }, 300); // 300ms delay
        });


        document.getElementById("confirmCheckout").addEventListener("click", async()=>{
            console.log("processing order");
            const data = await placeOrder()

            console.log(data);

            if(data.success == false){
                showToast(data.error || "Could not place order")
                return
            }

            //add form details
            await AddFormDetails(data.form)
            //submit form
            document.getElementById("paymentForm").requestSubmit();
        })


    } catch (error) {
        console.log(error);
    }


})




async function getUserInfo(){
    return new Promise((res, rej)=>{

        const response = fetch("/api/user/getUserInfo.php", {
            method: "POST",
            credentials: "include",
        })
        .then(res => res.json())
        .then(data =>{
            console.log(data);
            res(data)
        })
        .catch(error =>{
            console.log(error);
            rej({
                "status" : "failed",
                "success" : false,
                "error" : error || "Could not get user info"
            })
        })
    })
}




async function placeOrder(){
    const fName = document.getElementById("fName")
    const lName = document.getElementById("lName")
    const email = document.getElementById("email")
    const cell = document.getElementById("cell")
    const streetAddress = document.getElementById("userStreet")//delivery method
    const province = document.getElementById("province")
    const city = document.getElementById("city")
    const postal = document.getElementById("postal")
    const shippingMethods = document.querySelectorAll("#shippingMethod")

    const shippingMethodArray = Array.from(shippingMethods)

    let shippingMethod = ""
    shippingMethodArray.forEach((element)=>{
        if(element.checked){
            shippingMethod = element.name
        }
    })

    console.log(shippingMethod);


    const userCart = new Cart()
    userCart.init()
    const order = userCart.getCartItems()

    return new Promise((res, rej)=>{

        console.log(validateInput(fName, lName, email, cell, streetAddress, province, city, postal));

        try {
            if(validateInput(fName, lName, email, cell, streetAddress, province, city, postal)){




                const response = fetch("/api/payfast/placeOrder.php", {
                    method: "POST",
                    credentials: "include",
                    body : JSON.stringify({ 
                        "fName" : fName.value,
                        "lName" : lName.value,
                        "email" : email.value,
                        "cell" : cell.value,
                        "streetAddress" : streetAddress.value,
                        "province" : province.value,
                        "city" : city.value,
                        "postal" : postal.value,
                        "shippingMethod" : shippingMethod,
                        "orders" : order
                    })
                })
                .then(res => res.json())
                .then(data =>{
                    console.log(data);
                    res(data)
                })
                .catch(error =>{
                    console.log(error);
                    rej({
                        "status" : "failed",
                        "success" : false,
                        "error" : error || "Could not place order"
                    })
                })
            }//end if
        } catch (error) {
            console.log(error);
            rej({
                "status" : "failed",
                "success" : false,
                "error" : error || "Could not place order"
            })
        }
    })

}

async function AddFormDetails(form) {
    document.getElementById("paymentForm").innerHTML = `${form}`
}





async function createPaymentRequest(productID) {
    
    
    return new Promise((res, rej)=>{

        const response = fetch("/api/payfast/paymentRequest.php", {
            method: "POST",
            credentials: "include",
            body : JSON.stringify({ productID : productID })
        })
        .then(res => res.json())
        .then(data =>{
            console.log(data);
            res(data)
        })
        .catch(error =>{
            console.log(error);
            rej({
                "status" : "failed",
                "success" : false,
                "error" : error || "Could not create payment request"
            })
        })
    })


}


async function generatePaymentForm(data){
    const PaymentForm = document.getElementById("paymentForm")
    console.log(data);
    const product = data.product;
    const buyerInfo = data.buyerInfo

    console.log(data.form);

    PaymentForm.innerHTML += data.form

    document.getElementById("product_name").value = product.name
    document.getElementById("product_price").value = product.price

    document.getElementById("user_email").value = buyerInfo.email
    document.getElementById("user_name").value = buyerInfo.fName
    document.getElementById("user_surname").value = buyerInfo.lName

}





async function getListingDetails(id){
    return new Promise((res, rej)=>{
        const response = fetch("/api/payfast/getListingPaymentInfo.php",{
            method : "POST",
            credentials : "include",
            body : JSON.stringify({
                productID : id
            }) 
        })
        .then(res => res.json())
        .then(data=>{

            if(!data.success === true){
                throw data.error
            }

            console.log(data);
            res(data)
        })
        .catch(error=>{
            rej({
                "status" : "failed",
                "success" : false,
                "error" : error || "Could not get listing details"
            })
        })

    })
}



function renderAddressDropdown(results) {
    const dropdown = document.getElementById("addressDropdown");

    dropdown.innerHTML = "";

    if (!results.length) {
        dropdown.style.display = "none";
        return;
    }

    results.forEach(place => {
        const item = document.createElement("div");

        item.classList.add("dropdown-item");

        item.textContent = place.display_name;

        const city = place.address.city || place.address.town || place.address.village || place.address.county
        const province = place.address.state
        const userStreet = place.display_name

        item.addEventListener("click", () => {

            console.log(place);

            document.getElementById("province").value = province;
            document.getElementById("city").value = city;
            document.getElementById("userStreet").value = userStreet;
            dropdown.innerHTML = "";
            dropdown.style.display = "none";



            console.log(place);
        });

        dropdown.appendChild(item);
    });

    dropdown.style.display = "block";
}





async function renderCartItems(){
    const userCart = new Cart();
    await userCart.init();
    const cartItems = await userCart.getCartItems()


    const orderContainer = document.getElementById("orderContainer")

    orderContainer.innerHTML = `<p class="section-title">Your order</p>`

    cartItems.forEach((item)=>{
        orderContainer.innerHTML += 
        `
        <div class="order-item">
            <div>
                <img class="item-img"
                    src="${IMAGES_URL}/${item.productID}_a.webp"
                    alt="${item.name}"
                >
            </div>
            <div>
                <div class="item-name">${item.name}</div>
                <div class="item-sub">${item.quantity}</div>
            </div>
            <span class="item-price">R ${item.price}</span>
        </div>
        
        `
    })
    

    

}


//validate an array of inputs
//valid if it has a value 
function validateInput(...elements){
    
    for (let elm of elements) {
        console.log(elm);
        if(elm.tagName.toLowerCase() === "input" || elm.tagName.toLowerCase() === "textarea"){//check input and textarea elements for value
            if(!elm.value || elm.value.trim().length === 0){
                elm.focus()
                showToast("Fill in all fields");
                return false;
            }
        }else if(elm.tagName.toLowerCase() === "select"){//check select elements for a value
            //invalid value if index == 0
            if(elm.selectedIndex == 0){
                elm.focus()
                showToast("Fill in all fields");
                return false;
            }  
        }else{
            //error if we did not check all the input tags
            showToast("Unknown validation error");
            return false;
        } 
    }
    return true;
}




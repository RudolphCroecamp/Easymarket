

//verify bank information, add listing details to it, add payment button, go to payment processing after clicking pay



document.addEventListener("DOMContentLoaded", async ()=>{

    const params = new URLSearchParams(location.search)
    const productID = params.get("productID")

    console.log(productID);

    try {
        //show loading screen
        // document.getElementById("mainContent").style.display = "block";
        const loader = document.getElementById("pageLoader");

        //get product info
        const data = await createPaymentRequest(productID)

        //add data to form
        await generatePaymentForm(data)

        
        //hide loader after content loaded
        loader.classList.add("hidden");

        // Remove from DOM after animation
        setTimeout(() => {
            loader.remove();
        }, 400);

    } catch (error) {
        console.log(error);
    }


})


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
        .then(res => res.json)
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

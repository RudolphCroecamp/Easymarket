        
import {BACKEND_URL} from "../config.js"

//handle errors
import {showToast} from "../toast.js"
import {setErrorMessage, hideErrorMessage} from "../handleErrorMessage.js"

export {deleteListing, makeAvailable}






        
//make it unavailable 
function deleteListing(productID) {
    console.log(productID);
    const listing = document.getElementById("makeListingUnavailable")
    //delete products
    fetch(`${BACKEND_URL}/listings/deleteListing.php`, {
        method : "POST",
        credentials : "include",
        body : JSON.stringify({ productID: productID })
        
    })
    .then(res => res.json())
    .then(data => {
        console.log(data);

        if(data.success === true){
            showToast(data.message)
        }else{
            showToast(data.error || "Could not delete listing. Try again later.")
        }
        
        location.reload();
    })
}


//make it available 
function makeAvailable(productID) {
    console.log(productID);

    //delete products
    fetch(`${BACKEND_URL}/listings/makeAvailable.php`, {
        method : "POST",
        credentials : "include",
        body : JSON.stringify({ productID: productID })
        
    })
    .then(res => res.json())
    .then(data => {
        console.log(data);

        if(data.success === true){
            showToast(data.message)
        }else{
            showToast(data.error || "Could not make listing available. Try again later.")
        }
        
        location.reload();
    })
}
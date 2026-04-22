        
// import {BACKEND_URL} from "../config.js"

const BACKEND_URL="http://localhost:80/easymarket-api/endpoints/listings"
        
//make it unavailable 
function deleteListing(productID) {
    console.log(productID);

    //delete products
    fetch(`${BACKEND_URL}/deleteListing.php`, {
        method : "POST",
        credentials : "include",
        body : JSON.stringify({ productID: productID })
        
    })
    .then(res => res.json())
    .then(data => {
        console.log(data);

        if(data.success === true){
            alert(data.message)
        }else{
            alert(data.error || "Could not delete listing. Try again later.")
        }
        
        location.reload();
    })
}


//make it available 
function makeAvailable(productID) {
    console.log(productID);

    //delete products
    fetch(`${BACKEND_URL}/makeAvailable.php`, {
        method : "POST",
        credentials : "include",
        body : JSON.stringify({ productID: productID })
        
    })
    .then(res => res.json())
    .then(data => {
        console.log(data);

        if(data.success === true){
            alert(data.message)
        }else{
            alert(data.error || "Could not make listing available. Try again later.")
        }
        
        location.reload();
    })
}
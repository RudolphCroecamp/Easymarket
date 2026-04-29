import {BACKEND_URL, IMAGES_URL} from "../config.js"

//handle errors
import {showToast} from "../toast.js"
import {setErrorMessage, hideErrorMessage} from "../handleErrorMessage.js"

let page = 1;
let loading = false;

const container = document.getElementById("products-container");


loadListings();


//load more products as you reach the end
window.addEventListener("scroll", () => {
    if(window.innerHeight + window.scrollY >= document.body.offsetHeight - 200){
        loadListings();
    }
});


document.addEventListener("DOMContentLoaded", () => {
    const filter = document.getElementById("filterListings");

    filter.addEventListener("change", () => {
        page = 1;
        loading = false;
        container.innerHTML = "";
        loadListings();
    });
});




function loadListings(){
    if(loading) return;//prevent the client from making unwanted request

    loading = true;

    const filterListings = document.getElementById("filterListings").value

    //get products
    fetch(`${BACKEND_URL}/listings/getListings.php?page=${page}&filterListings=${filterListings}`, {
        method : "GET",
        credentials : "include",
    })
    .then(res => res.json())
    .then(data => {
        
        if(data.success===false){
            //log error on failed
            throw new Error(data.error); 
        }else{
            console.log(data);

            data.products.forEach(product => {

            //add product into container
            container.innerHTML += `
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card shadow-sm">
                        <img src="${BACKEND_URL}/listings/uploads/${product.productID}_a.jpg" class="card-img-top listingImg">

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-nowrap">${product.name}</h5>
                            <p class="text-muted text-nowrap">R${product.price}</p>
                            
                            ${
                                product.deleted
                                ? `
                                    <span class="badge mb-2 bg-danger">
                                        unavailable
                                    </span>

                                    <div class="mt-auto d-flex gap-2">
                                        <button class="btn btn-sm btn-success" data-id="${product.productID}" onclick="handle_makeAvailable(event)">List</button>
                                    </div>
                                `
                                : `
                                    <span class="badge mb-2 bg-success">
                                        available
                                    </span>

                                    <div class="mt-auto d-flex gap-2">
                                        <a class="btn btn-sm btn-primary" href="/src/pages/listings/updateListing.html?productID=${product.productID}">Edit</a>
                                        <button class="btn btn-sm btn-danger" data-id="${product.productID}" onclick="handle_deleteListing(event)">Delete</button>
                                        <a class="btn btn-sm btn-outline-secondary" href="/src/pages/products/product-view.html?productID=${product.productID}">View</a>
                                    </div>
                                `
                            }
                        </div>
                    </div>
                </div>
                `;})

            page++;
            loading = false;
        }

        

    }).catch(error =>{
        console.log(error)

        //show error message to client
        document.getElementById("error-box").classList.remove("visually-hidden");
        document.getElementById("error-message").innerText = error;
    });

}




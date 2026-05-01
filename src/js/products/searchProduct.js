import {BACKEND_URL, IMAGES_URL} from "../config.js"

//handle errors
import {showToast} from "../toast.js"
import {setErrorMessage, hideErrorMessage} from "../handleErrorMessage.js"

console.log("running here");
let page = 1;
let loading = false;

const container = document.getElementById("searchProducts-container");

//get query param from url
let params = new URLSearchParams(location.search);
const query = params.get('query')

//check that we have a value before making request to server
if(!query || query.length === 0){
    console.log({"error" : "no query found in url"});
    //go back to home page if no query where found in params
    window.location ="/"
}

//load more products as you reach the end
window.addEventListener("scroll", () => {
    if(window.innerHeight + window.scrollY >= document.body.offsetHeight - 200){
        searchProduct(query)
    }
});


searchProduct(query)


function searchProduct(query){
    //prevent the client from making unwanted request
    if(loading) return;
    loading = true;

    console.log(query);

    //get products form server
    fetch(`${BACKEND_URL}/products/searchProduct.php?page=${encodeURIComponent(page)}&query=${encodeURIComponent(query)}`, {
        method : "POST",
        credentials : "include"
    })
    .then(res => res.json())
    .then(data =>{

        if(data.success===false){
            //log error on failed
            throw new Error(data.error); 
        }else{
            console.log(data);

            //hide spinner when there arn't more items to load
            if(data.products.length < 20){
                const spinner = document.getElementById('loading');
                spinner.style.display = 'none';
            }

            data.products.forEach(product => {
                //add product into container
                container.innerHTML += `
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card ">

                            <img src="${IMAGES_URL}/${product.image}_a.webp" class="card-img-top">

                            <div class="card-body">

                                <h5>${product.name}</h5>
                                <p>Sold by ${product.id}</p>
                                <p>$${product.price}</p>

                                <div class="mt-auto">
                                    <button class="btn btn-primary w-100">
                                        <a class="text-white text-decoration-none" href="./product-view.html?productID=${product.productID}">View Product</a>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `
            });

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

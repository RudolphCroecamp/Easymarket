import {BACKEND_URL, IMAGES_URL} from "../config.js"

//handle errors
import {showToast} from "../toast.js"
import {setErrorMessage, hideErrorMessage} from "../handleErrorMessage.js"

let page = 1;
let loading = false;

const container = document.getElementById("products-container");


loadProducts();


//load more products as you reach the end
window.addEventListener("scroll", () => {
    if(window.innerHeight + window.scrollY >= document.body.offsetHeight - 200){
        loadProducts();
    }
});

function loadProducts(){
    //prevent the client from making unwanted request
    if(loading) return;

    loading = true;

    //get products
    fetch(`${BACKEND_URL}/products/getproducts.php?page=${page}`, {
        method : "POST",
        credentials : "include",
        param : {
            page : page,
        }
    })
    .then(res => res.json())
    .then(data => {
        
        if(data.success===false){
            //log error on failed
            throw new Error(data.error); 
        }else{
            console.log(data);

            //hide spinner when there are more items to load
            if(data.products.length < 20){
                const spinner = document.getElementById('loading');
                spinner.style.display = 'none';
            }

            data.products.forEach(product => {

                //add product into container
                container.innerHTML += 
                `
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card shadow-sm border-0 h-100 product-card">

                            <!-- Image -->
                            <div class="p-3 text-center">
                                <img src="${IMAGES_URL}/${product.primaryImage}" 
                                    class="img-fluid rounded"
                                    style="height: 180px; object-fit: contain;">
                            </div>

                            <!-- Body -->
                            <div class="card-body d-flex flex-column">
                                
                                <h6 class="fw-semibold mb-1 text-truncate">
                                    ${product.name}
                                </h6>

                                <p class="text-muted small mb-2 text-truncate">
                                    Sold by ${product.ownerName}
                                </p>

                                <h5 class="fw-bold text-primary mb-3">
                                    $${product.price}
                                </h5>

                                <!-- Button -->
                                <a href="./src/pages/products/product-view.html?productID=${product.productID}" 
                                class="btn btn-dark mt-auto w-100">
                                    View Product
                                </a>

                            </div>
                        </div>
                    </div>
                `});

            page++;
            loading = false;
        }

        

    }).catch(error =>{
        console.log(error)
        //show error message to client
        setErrorMessage(error)
    });

}
import {BACKEND_URL, IMAGES_URL} from "../config.js"

import {getLocationFromGPS, searchAddresses} from "../locationServices.js"

//handle errors
import {showToast} from "../toast.js"
import {setErrorMessage, hideErrorMessage} from "../handleErrorMessage.js"


let page = 1;
let loading = false;

const MAX_LISTING_PRICE = 50_000

const FILTERS = {}

const container = document.getElementById("products-container");


//handle filters
const filterbtn = document.getElementById("applyFilters")
const addressInput = document.getElementById("addressInput")
const priceRange = document.getElementById("priceSlider")

const spinner = document.getElementById('loading');


let MIN = 0
let MAX = 50_000
let LAT = 0
let LONG = 0
let RADIUS = 60//default of 60

document.addEventListener("DOMContentLoaded", async ()=>{
    await address_init()

    await loadProducts(MIN, MAX, LAT, LONG, RADIUS)

    let debounceTimer;

    document.getElementById("addressInput").addEventListener("input", (e) => {
        clearTimeout(debounceTimer);

        const query = e.target.value;

        debounceTimer = setTimeout(async () => {
            const results = await searchAddresses(query);
            renderAddressDropdown(results);
        }, 300); // 300ms delay
    });


    const radiusInput = document.getElementById("radiusInput")
    const submitLocationBtn = document.getElementById("submitLocation")
    
    submitLocationBtn.addEventListener("click", async ()=>{
        console.log(radiusInput.value);
        RADIUS = radiusInput.value

        console.log("filtering");

        container.innerHTML = ""
        page = 1

        await loadProducts(MIN, MAX, LAT, LONG, RADIUS)
        console.log("done filtering");
        
    })

})


//load more products as you reach the end
window.addEventListener("scroll", async () => {
    if(window.innerHeight + window.scrollY >= document.body.offsetHeight - 200){
        await loadProducts(MIN, MAX, LAT, LONG, RADIUS);
    }
});

async function address_init(){
    const userLocationData = await getLocationFromGPS();

    if(userLocationData.success === false){
        throw new Error("Could not find address");
    }

    const {province, city, lat, long} = userLocationData
    LAT = lat
    LONG = long

    addressInput.value = `${city}, ${province}`
}


async function loadProducts(min=0, max=50_000, lat, long, radius){
    //prevent the client from making unwanted request
    if(loading) return;

    console.log(min, max, lat, long, radius);

    loading = true;

    //make sppinner visible when making request -> show loading state
    spinner.style.display = 'block';

    //get products
    fetch(`${BACKEND_URL}/products/getproducts.php`, {
        method: "POST",
        credentials: "include",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            page,
            min,
            max,
            lat,
            long,
            radius
        })
    })
    .then(res => res.json())
    .then(data => {
        console.log(data);
        
        if(data.success===false){
            //log error on failed
            throw new Error(data.error); 
        }else{
            hideErrorMessage()
            console.log(data);

            //hide spinner when there are more items to load
            if(data.products.length < 20){
                spinner.style.display = 'none';
            }

            data.products.forEach(product => {

                //add product into container
                container.innerHTML += `
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="./src/pages/products/product-view.html?productID=${product.productID}" 
                        class="text-decoration-none text-dark">

                            <div class="card h-100 border-0 shadow-sm product-card overflow-hidden">

                                <!-- Image -->
                                <div class="product-image-wrapper">
                                    <img 
                                        src="${IMAGES_URL}/${product.primaryImage}" 
                                        class="w-100 product-image lazy-image"
                                        alt="${product.name}"
                                        loading="lazy"
                                    >
                                </div>

                                <!-- Body -->
                                <div class="card-body d-flex flex-column">

                                    <h6 class="fw-semibold mb-1 text-truncate">
                                        ${product.name}
                                    </h6>

                                    <p class="text-muted small mb-1 text-truncate">
                                        Sold by ${product.ownerName || "Pieter Jacobs"}
                                    </p>

                                    <p class="text-muted small mb-2">
                                        ${product.city}, ${product.province}
                                    </p>

                                    <div class="mt-auto">
                                        <h5 class="fw-bold text-primary mb-0">
                                            R${product.price}
                                        </h5>
                                    </div>

                                </div>
                            </div>
                        </a>
                    </div>
                `});

            page++;
            loading = false;
        }

        

    }).catch(error =>{
        console.log(error)
        //show error message to client
        setErrorMessage(error)
        //hide spinner after request failed -> close loading state
        spinner.style.display = 'none';
        loading = false;
    });

}


document.addEventListener("load", function(e) {
    if (e.target.classList.contains("lazy-image")) {
        e.target.classList.add("loaded");
    }
}, true);



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

        item.addEventListener("click", () => {
            document.getElementById("addressInput").value = `${city}, ${place.address.state}`;
            dropdown.innerHTML = "";
            dropdown.style.display = "none";

            LAT = place.lat
            LONG = place.lon

            console.log(place);
        });

        dropdown.appendChild(item);
    });

    dropdown.style.display = "block";
}





const minPriceInput = document.getElementById("minPriceInput")
const maxPriceInput = document.getElementById("maxPriceInput")

minPriceInput.addEventListener("change", ()=>{
    if(minPriceInput.value <= 0){
        MIN = 0
        minPriceInput.value = 0
        return
    }

    MIN = minPriceInput.value
})

maxPriceInput.addEventListener("change", ()=>{
    if(maxPriceInput.value >= MAX_LISTING_PRICE){
        MAX = 50_000
        maxPriceInput.value = 50_000
        return
    }

    MAX = maxPriceInput.value
})






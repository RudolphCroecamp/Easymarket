import {BACKEND_URL, IMAGES_URL} from "../config.js"

//handle errors
import {showToast} from "../toast.js"
import {setErrorMessage, hideErrorMessage} from "../handleErrorMessage.js"

import categoryData_init, {popSubContiner_onselect__cat} from "../loadData/categories.js";
import locationData_init, {popSubContainer_onselect_loc} from "../loadData/locations.js";
import { searchAddresses } from "../locationServices.js";

const dropArea = document.getElementById('drop-area');
const fileInput = document.getElementById('images');
const preview = document.getElementById('preview');

categoryData_init()
locationData_init()


document.addEventListener("DOMContentLoaded", ()=>{

    //get productID
    const params = new URLSearchParams(location.search);
    const productID = params.get('productID')

    loadProduct(productID)
})


let filesToUpload = [];
let existingImages = [];
// let deletedImages = [];

//global
let numberOfCurrentImages = 0;

//Click drop area to open file selector
dropArea.addEventListener('click', () => fileInput.click());

//Handle file input
fileInput.addEventListener('change', () => addFiles(fileInput.files));

//Drag and drop events
dropArea.addEventListener('dragover', e => {
    e.preventDefault();
    dropArea.classList.add('dragover');
});
dropArea.addEventListener('dragleave', () => dropArea.classList.remove('dragover'));
dropArea.addEventListener('drop', e => {
    e.preventDefault();
    dropArea.classList.remove('dragover');
    addFiles(e.dataTransfer.files);
});


// Function to add files and show previews
function addFiles(files) {
    for (let file of files) {
        if (!file.type.startsWith('image/')) continue;

        const index = filesToUpload.length;
        filesToUpload.push(file);

        const reader = new FileReader();
        reader.onload = e => {
            // wrapper div
            const wrapper = document.createElement('div');
            wrapper.classList.add('preview-item');

            // image
            const img = document.createElement('img');
            img.src = e.target.result;

            // remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button'; // important!
            removeBtn.classList.add('remove-btn');
            removeBtn.innerHTML = '&times;';
            removeBtn.addEventListener('click', (event) => {
                event.stopPropagation(); // prevent triggering drop-area click
                filesToUpload.splice(index, 1);
                wrapper.remove();
            });

            wrapper.appendChild(img);
            wrapper.appendChild(removeBtn);
            preview.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    }
}



//handle listing details upload
const form = document.getElementById('newLisitingForm');

form.addEventListener('submit', async(e) => {
    e.preventDefault();
    hideErrorMessage()

    const title = document.getElementById("title")
    const price = document.getElementById("price")
    const condition = document.getElementById("condition")
    const description = document.getElementById("description")
    const delivery = document.getElementById("delivery")//delivery method
    const province = document.getElementById("province")
    const city = document.getElementById("city")
    const category = document.getElementById("category")
    const subcategory = document.getElementById("subcategory")
    const quantity = document.getElementById("quantity")

    if(
        validateInput(title, price, condition, description, delivery, province, city, category, subcategory, quantity)
    ){
        //get coordinates of user from dropdown boxes
        const query = province.value + " " + city.value
        const data = await searchAddresses(query)
        const {lat, lon} = data[0]

        if(!lat || !lon){
            throw new Error("Could not get coordinates");
        }

        const formData = new FormData();

        // append only dragged/selected files manually
        filesToUpload.forEach(file => formData.append('images[]', file));


        // append all other fields manually
        formData.append("title", title.value);
        formData.append("price", price.value);
        formData.append("category", category.value);//default other
        formData.append("condition", condition.value);//default new
        formData.append("description", description.value);
        formData.append("delivery", delivery.value);
        formData.append("province", province.value);
        formData.append("city", city.value);
        formData.append("subcategory", subcategory.value);
        formData.append("latitude", lat);
        formData.append("longitude", lon);
        formData.append("quantity", quantity.value || 1);
        formData.append("numberOfCurrentImages", numberOfCurrentImages)

        formData.append("filesToKeep", JSON.stringify(existingImages))
        // formData.append("filesToDelete",JSON.stringify(deletedImages));


        //get tags from tagsInput
        const tagsArray = tagify.value.map(tag => tag.value);


        //tags could be empty becasue it is optional
        if(tagsArray.length > 0) formData.append("tags", JSON.stringify(tagsArray));

        //get productID
        const params = new URLSearchParams(location.search);
        const productID = params.get('productID')

        formData.append("productID", productID)
        
        document.getElementById("loadingOverlay").classList.remove("d-none")//show loader
        try {
            fetch(`${BACKEND_URL}/listings/updateListing.php`, {
                method: 'POST',
                credentials : "include",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                console.log(data);

                //redirect back to home screen or show error
                if(data.success === true){
                    showToast(data.message, "success")
                    window.location = "/"
                    form.reset()
                }else{
                    setErrorMessage(data.error)
                    showToast(data.message || data.error, "warning")
                    document.getElementById("loadingOverlay").classList.add("d-none")//remove loader
                }
            })

        } catch (err) {
            console.error(err);
            document.getElementById("loadingOverlay").classList.add("d-none")//remove loader
        }

    }

    
});



//get and return the users latitude and longitude
function getUserLocation() {
    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                resolve({
                    lat: lat,
                    lng: lng
                });
            },
            (error) => {
                reject(error);
            },
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            }
        );
    });
}


//reset page and redirect back to home screen
form.addEventListener("reset", ()=>{
    window.location = "/"
})



//validate an array of inputs
//valid if it has a value 
function validateInput(...elements){
    
    for (let elm of elements) {
        console.log(elm.value);

        if(elm.tagName.toLowerCase() === "input" || elm.tagName.toLowerCase() === "textarea"){//check input elements for value
            if(!elm.value || elm.value.trim().length === 0){
                elm.focus()
                setErrorMessage("Fill in all fields");
                return false;
            }
        }else if(elm.tagName.toLowerCase() === "select"){//check select elements for a value
            //invalid value if index == 0
            if(elm.selectedIndex == 0){
                elm.focus()
                setErrorMessage("Fill in all fields");
                return false;
            }  
        }else{
            //error if we did not check all the input tags
            setErrorMessage("Unknown validation error");
            return false;
        } 
    }
    return true;
}



const input = document.getElementById("tagsInput");

// Initialize Tagify
const tagify = new Tagify(input, {
    delimiters: ",| ", // Enter, comma, or space
    maxTags: 10,
    whitelist: [], // optional: predefined suggestions
    dropdown: {
        enabled: 0
    }
});



//load product data
async function loadProduct(productID) {
    //get products
    fetch(`${BACKEND_URL}/products/getProductById.php?productID=${productID}`, {
        method : "POST",
        credentials : "include"
    })
    .then(res => res.json())
    .then(data => {

        const product = data.product
        console.log(data);

        title.value = product.name 
        price.value = product.price
        description.value = product.description
        quantity.value = product.quantity

        setSelectByText(condition, product.condition);
        setSelectByText(delivery, product.delivery);
        setSelectByText(category, product.category);
        setSelectByText(subcategory, product.subcategory);
        setSelectByText(province, product.province);
        setSelectByText(city, product.city);

        numberOfCurrentImages = product.imageCount;
        let imagePath = "";
        for (let i = 0; i < numberOfCurrentImages; i++) {
            imagePath = `${IMAGES_URL}/${productID}_${String.fromCharCode(97 + i)}.webp`
            addExistingImage(imagePath)
        }

    })
    .catch(error =>{
        console.log(error.message);
    })


}


function setSelectByText(select, text) {
    console.log("ini", select, text);
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value === text) {
            select.selectedIndex = i;

            // trigger change
            select.dispatchEvent(new Event('change', {
                bubbles: true
            }));
            return true;
        }
    }
    return false;
}


function addExistingImage(imageUrl) {

    existingImages.push(imageUrl);

    const wrapper = document.createElement('div');
    wrapper.classList.add('preview-item');

    const img = document.createElement('img');
    img.src = imageUrl;

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.classList.add('remove-btn');
    removeBtn.innerHTML = '&times;';

    removeBtn.addEventListener('click', () => {
        //remove from existingImages
        existingImages = existingImages.filter(
            image => image !== imageUrl
        );

        //delete tracking
        // deletedImages.push(imageUrl);

        wrapper.remove();
    });

    wrapper.appendChild(img);
    wrapper.appendChild(removeBtn);

    preview.appendChild(wrapper);
}












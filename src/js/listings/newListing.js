import {BACKEND_URL} from "../config.js"

import {categoryOptions} from "./listingOptions.js"

const dropArea = document.getElementById('drop-area');
const fileInput = document.getElementById('images');
const preview = document.getElementById('preview');

const categoryContainer = document.getElementById("category")
const conditionContainer = document.getElementById("condition")
const amountTypeContainer = document.getElementById("amountType")
const deliveryContainer = document.getElementById("delivery")


let filesToUpload = [];

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

form.addEventListener('submit', async e => {
    e.preventDefault();
    hideErrorMessage()


    console.log("requesting");
    const title = document.getElementById("title")
    const price = document.getElementById("price")
    const category = document.getElementById("category")
    const condition = document.getElementById("condition")
    const description = document.getElementById("description")
    const amountType = document.getElementById("amountType")
    const quantity = document.getElementById("quantity")//optional defaults to 1
    const delivery = document.getElementById("delivery")//delivery method

    // console.log(validateInput(title, price, category, condition, description, amountAvailable, delivery));

    if(
        validateInput(title, price, category, condition, description, amountType, delivery)
    ){
        const formData = new FormData();

        // append only dragged/selected files manually
        filesToUpload.forEach(file => formData.append('images[]', file));

        //set select elements value
        const categoryText = categoryContainer.options[categoryContainer.selectedIndex].text
        const conditionText = conditionContainer.options[conditionContainer.selectedIndex].text
        const amountTypeText = amountTypeContainer.options[amountTypeContainer.selectedIndex].text
        const deliveryText = deliveryContainer.options[deliveryContainer.selectedIndex].text

        // append all other fields manually
        formData.append("title", title.value);
        formData.append("price", price.value);
        formData.append("category", categoryText);//default other
        formData.append("condition", conditionText);//default new
        formData.append("description", description.value);
        formData.append("amountType", amountTypeText);//default single item
        formData.append("quantity", quantity.value || 1);//default 1
        formData.append("location", location.value);
        formData.append("delivery", deliveryText);

        const coords = await getUserLocation()
        console.log(coords);
        formData.append("latitude", coords.latitude);
        formData.append("longitude", coords.longitude);

        //get tags from tagsInput
        const tagsArray = tagify.value.map(tag => tag.value);

        //tags array
        formData.append("tags", JSON.stringify(tagsArray));

        try {
            fetch(`${BACKEND_URL}/listings/newListing.php`, {
                method: 'POST',
                credentials : "include",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                console.log(data);

                //redirect back to home screen or show error
                if(data.success === true){
                    // window.location = "/"
                    // document.getElementById("newLisitingForm").reset()
                }else{
                    setErrorMessage(data.error)
                }
            })

        } catch (err) {
            console.error(err);
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

//show preview of page
document.getElementById("btnPreviewListing")
.addEventListener("click", ()=>{
    window.location = "/preview-listing"
})





//add or remove quantity input from client screen
document.getElementById("amountType")
.addEventListener("click", (elm)=>{

    if(elm.target.value == 1){
        document.getElementById("quantityContainer").classList.add("visually-hidden");
    }else if(elm.target.value == 2){
        document.getElementById("quantityContainer").classList.remove("visually-hidden");
    }
})



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







//append categories to category dropdown container / select element
categoryOptions.forEach((cat, index) =>{
    categoryContainer.innerHTML +=`<option value="${index+1}">${cat}</option>` 
})











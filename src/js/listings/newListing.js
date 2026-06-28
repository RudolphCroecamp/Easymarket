import {BACKEND_URL} from "../config.js"

//handle errors
import {showToast} from "../toast.js"
import {setErrorMessage, hideErrorMessage} from "../handleErrorMessage.js"

import categoryData_init from "../loadData/categories.js";
import locationData_init from "../loadData/locations.js";
import { searchAddresses } from "../locationServices.js";

const dropArea = document.getElementById('drop-area');
const fileInput = document.getElementById('images');
const preview = document.getElementById('preview');


categoryData_init()
locationData_init()


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
        formData.append("category", category.value);
        formData.append("condition", condition.value);
        formData.append("description", description.value);
        formData.append("delivery", delivery.value);
        formData.append("province", province.value);
        formData.append("city", city.value);
        formData.append("subcategory", subcategory.value);
        formData.append("latitude", lat);
        formData.append("longitude", lon);
        formData.append("quantity", quantity.value || 1);

        //get tags from tagsInput
        const tagsArray = tagify.value.map(tag => tag.value);


        //tags could be empty becasue it is optional
        if(tagsArray.length > 0) formData.append("tags", JSON.stringify(tagsArray));
        

        try {
            fetch(`${BACKEND_URL}/listings/newListing.php`, {
                method: 'POST',
                credentials : "include",
                body: formData
            })
            .then(res => res.text())
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
                }
            })

        } catch (err) {
            console.error(err);
        }

    }

    
});


//reset page and redirect back to home screen
form.addEventListener("reset", ()=>{
    window.location = "/"
})




//validate an array of inputs
//valid if it has a value 
function validateInput(...elements){
    
    for (let elm of elements) {
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


// Initialize Tagify
const input = document.getElementById("tagsInput");

const tagify = new Tagify(input, {
    delimiters: ",| ", // Enter, comma, or space
    maxTags: 10,
    whitelist: [], // optional: predefined suggestions
    dropdown: {
        enabled: 0
    }
});


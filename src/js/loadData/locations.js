export default locationData_init

export {loadLocationData, popMainContiner_loc, popSubContainer_onselect_loc}

const BASE_PATH = window.location.pathname.split("/")[1];

const mainContainer = document.getElementById("province")
const subContainer = document.getElementById("city")

async function locationData_init(){
    try {
        const locationData = await loadLocationData()

        if(!locationData || !locationData.provinces){
            throw new Error("Could not load locationData");
        }

        //we ghave data
        await popMainContiner_loc(locationData)

        mainContainer.addEventListener("change", async () => {
            popSubContainer_onselect_loc(locationData)
        })
    } catch (error) {
        console.log(error);
    }
}


//get data from locations.json
async function loadLocationData() {
    try {
        const response = await fetch(`/${BASE_PATH}/src/js/loadData/locations.json`);
        const data = await response.json();
        return data;
    } catch (err) {
        console.error(err);
    }
}

//populate containers
function popMainContiner_loc(locationData){
    //add provinces
    locationData.provinces.forEach((name)=>{
        const option = document.createElement("option");
        option.value = name.name;
        option.textContent = name.name;
        mainContainer.appendChild(option);
    })
}



function popSubContainer_onselect_loc(locationData){
    console.log(locationData);
    //disable subcategory select element
    if(mainContainer.value == "0"){
        //reset to defaults when no option was selected
        subContainer.disabled = true;
        subContainer.selectedIndex = 0;
        return
    }

    //enable city select element
    subContainer.disabled = false;

    const selected = locationData.provinces.find(p => p.name === mainContainer.value)

    subContainer.innerHTML = "";

    subContainer.innerHTML += "<option value='0' selected>Select a city</option>";

    selected.cities.forEach(city => {
        const option = document.createElement("option");
        option.value = city.name;
        option.textContent = city.alt 
        ? `${city.name} (${city.alt})` 
        : city.name;

        subContainer.appendChild(option);
    });
}



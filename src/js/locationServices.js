export default getUserLocation



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




function setCityFromGPS() {
    navigator.geolocation.getCurrentPosition(async (pos) => {
        const res = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`
        );

        const data = await res.json();

        console.log(data);

        const city = data.address.city ||
            data.address.town ||
            data.address.village ||
            "Unknown"

        let location = "Unknown"
        
        if(data.address.county && data.address.state){
            location = (data.address.county + ", " + data.address.state) 
        }

        console.log(city);

        // document.getElementById("setLocation").innerHTML = `<strong>Location:</strong> ${city}` 
        document.getElementById("setLocation").innerHTML = `${location}` 
    })

}

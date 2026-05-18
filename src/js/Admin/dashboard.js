


document.addEventListener("DOMContentLoaded", function () {
    const options = {
        chart: {
            type: 'area',
            height: 320,
            toolbar: { show: false }
        },
        series: [{
            name: 'GMV',
            data: []
        }],
        xaxis: {
            categories: [
                'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'
            ]
        },
        stroke: {
            curve: 'smooth'
        },
        fill: {
            type: 'gradient'
        }
    };

    gmvChart = new ApexCharts(document.querySelector("#gmvChart"), options);
    gmvChart.render();

    const retentionOptions = {
        chart: {
            type: 'bar',
            height: 320,
            toolbar: {
                show: false
            }
        },

        series: [{
            name: 'Users',
            data: [0, 0]
        }],

        xaxis: {
            categories: [
                'Repeat Buyers',
                'Repeat Sellers'
            ]
        },

        dataLabels: {
            enabled: true
        }
    };

    
    let retentionChart = new ApexCharts(document.querySelector("#retentionChart"), retentionOptions);
    retentionChart.render();


    fetch('/api/Admin/dashboard.php', {
        method: "POST",
        credentials: "include"
    })
    .then(res => res.json())
    .then(data => {

        console.log(data);

        gmvChart.updateOptions({
            series: [{
                name: 'GMV',
                data: data.gmvTrend.data
            }],
            xaxis: {
                categories: data.gmvTrend.labels
            }
        });

        // ✅ THIS NOW WORKS
        retentionChart.updateSeries([{
            data: [
                data.buyerRetention,
                data.sellerRetention
            ]
        }]);



        

        // KPI cards
        document.getElementById("gmvValue").innerText = "R " + data.gmv;
        document.getElementById("ordersValue").innerText = data.orders;
        document.getElementById("usersValue").innerText = data.activeUsers;
    });
});
/*
    SargaPay. Cardano gateway plug-in for Woocommerce. 
    Copyright (C) 2021  Sargatxet Pools

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//CountDown
const countDown = setInterval(() => {

    // Check if user is logged in
    const logged_in = window.hasOwnProperty("wp_ajax_nopriv_sargapay_get_settings_vars") ?
        wp_ajax_nopriv_sargapay_get_settings_vars.is_user_logged_in :
        wp_ajax_sargapay_get_settings_vars.is_user_logged_in

    if (logged_in) {
        try {
            // Get when the order was made timestamp and time to cancel order
            const p_timestamp = document.getElementById('sarga-timestamp')
            const p_time_wait = document.getElementById('sarga-time-wait')

            if (p_timestamp && p_time_wait) {

                // Add hrs to make the countdown   
                const countDownDate = (parseInt(p_timestamp.innerText) + (parseInt(p_time_wait.innerText)) * 60 * 60) * 1000

                // Get today's date and time
                let now = new Date().getTime();

                // Find the distance between now and the count down date
                let distance = countDownDate - now;

                // Time calculations for days, hours, minutes and seconds
                let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                let seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // If the count down is finished, write some text
                if (distance < 0) {
                    //|| distance > (24 * 60 * 60)) 
                    clearInterval(countDown);
                    document.getElementById("sarga-countdown").innerHTML = "EXPIRED";
                } else {
                    // Display CountDown
                    document.getElementById("sarga-countdown").innerHTML = hours + "h " +
                        minutes + "m " + seconds + "s ";
                }
            }
        } catch (error) {
            console.dir(error)
        }
    }
}, 1000);
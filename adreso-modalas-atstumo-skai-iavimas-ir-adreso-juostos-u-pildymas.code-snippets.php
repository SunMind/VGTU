<?php

/**
 * Adreso modalas, atstumo skaičiavimas ir adreso juostos užpildymas
 */
add_action('wp_footer', function() {
    ?>
    <!-- ✅ Modalas adresui įvesti -->
    <div id="addressModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;">
      <div style="background:white;max-width:400px;margin:10% auto;padding:30px;border-radius:10px;position:relative;text-align:center;">
        <h2>Kur pristatyti?</h2>
        <p style="font-size:14px;color:#444;">Įveskite savo adresą, kad galėtume parodyti artimiausius ūkius ir suplanuoti pristatymą.</p>
        <input type="text" id="userAddress" placeholder="Pvz. Vilniaus g. 10, Ukmergė" style="width:100%;padding:10px;margin:15px 0;">
        <button onclick="saveAddressAndContinue()" style="padding:10px 20px;background:#008e5d;color:white;border:none;border-radius:6px;">Tęsti</button>
      </div>
    </div>

    <!-- ✅ Google Maps Places API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD3YkNGPwswa8eGfRQYeS_6ixIS5I6gm2o&libraries=places&callback=initAutocomplete" async defer></script>

    <script>
    function initAutocomplete() {
        const input = document.getElementById('userAddress');
        if (!input) return;

        const autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['address'],
            componentRestrictions: { country: 'lt' }
        });

        autocomplete.addListener('place_changed', function () {
            const place = autocomplete.getPlace();
            if (place && place.formatted_address) {
                localStorage.setItem('userAddress', place.formatted_address);
                if (place.geometry && place.geometry.location) {
                    localStorage.setItem('userLat', place.geometry.location.lat());
                    localStorage.setItem('userLng', place.geometry.location.lng());
                }
                document.getElementById('addressModal').style.display = 'none';
                location.reload();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // 1. Rodo modalą jei nėra adreso
        const address = localStorage.getItem('userAddress');
        if (!address) {
            document.getElementById('addressModal').style.display = 'block';
        }

        window.saveAddressAndContinue = function () {
            const userAddress = document.getElementById('userAddress').value.trim();
            if (userAddress !== '') {
                localStorage.setItem('userAddress', userAddress);
                document.getElementById('addressModal').style.display = 'none';
                location.reload();
            } else {
                alert('Įveskite adresą');
            }
        };

        // 2. Parodo adresą viršuje (jei yra)
        const savedAddress = localStorage.getItem('userAddress');
        const container = document.getElementById('addressDisplay');
        const text = document.getElementById('savedAddressText');

        if (savedAddress && container && text) {
            text.innerText = savedAddress;
            container.style.display = 'block';
        }

        window.changeUserAddress = function () {
            localStorage.removeItem('userAddress');
            localStorage.removeItem('userLat');
            localStorage.removeItem('userLng');
            location.reload();
        };

        // 3. Skaičiuoja atstumą ir rūšiuoja
        const userLat = parseFloat(localStorage.getItem('userLat'));
        const userLng = parseFloat(localStorage.getItem('userLng'));

        if (!isNaN(userLat) && !isNaN(userLng)) {
            const farmCards = document.querySelectorAll('.farm-card');
            const farms = Array.from(farmCards).map(function(card) {
                const farmLat = parseFloat(card.getAttribute('data-lat'));
                const farmLng = parseFloat(card.getAttribute('data-lng'));
                const distance = calculateDistance(userLat, userLng, farmLat, farmLng);
                const rounded = Math.round(distance * 10) / 10;

                const farmDistance = card.querySelector('.farm-distance');
                if (farmDistance) {
                    farmDistance.textContent = `~${rounded} km nuo jūsų`;
                }

                return { card, distance };
            });

            farms.sort((a, b) => a.distance - b.distance);

            const grid = document.querySelector('.farmer-grid');
            farms.forEach(f => grid.appendChild(f.card));
        }
    });

    function calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    </script>
    <?php
});

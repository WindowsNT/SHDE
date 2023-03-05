'use strict';
var _x_name = 'Music School of Alimos';
var _x_url = 'https://www.msa-apps.com/shde';
var xev;

self.addEventListener('push', function(event) {
    console.log('[Service Worker] Push Received.');
    xev = event.data.text();
    console.log(`[Service Worker] Push had this data: "${event.data.text()}"`);
    const title = "SHDE";
    const options = {
      body: event.data.text(),
      icon: 'admin.png',
      badge: 'admin.png',
      click_action: _x_url,
  
    };
    event.waitUntil(self.registration.showNotification(title, options));
  });
  
  self.addEventListener('notificationclick', function(event) {
    console.log('[Service Worker] Notification click Received.');
  
    event.notification.close();
  
    var j = _x_url + xev; 
    event.waitUntil(
      clients.openWindow(j)
    );
  });

  

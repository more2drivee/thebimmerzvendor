<script>
const notificationTranslations = {!! json_encode([
    'notifications' => __('notifications.notifications'),
    'unread' => __('notifications.unread'),
    'total' => __('notifications.total'),
    'search_placeholder' => __('notifications.search_placeholder'),
    'all' => __('notifications.all'),
    'pending' => __('notifications.pending'),
    'approved' => __('notifications.approved'),
    'rejected' => __('notifications.rejected'),
    'noNotifications' => __('notifications.noNotifications'),
    'errorLoading' => __('notifications.errorLoading'),
    'noDataServer' => __('notifications.noDataServer'),
    'reservation' => __('notifications.reservation'),
    'jobOrder' => __('notifications.jobOrder'),
    'info' => __('notifications.info'),
    'customer' => __('notifications.customer'),
    'device' => __('notifications.device'),
    'location' => __('notifications.location'),
    'note' => __('notifications.note'),
    'approve' => __('notifications.approve'),
    'reject' => __('notifications.reject'),
    'justNow' => __('notifications.justNow')
]) !!};

const lang = document.documentElement.lang || 'en';
const t = (key) => notificationTranslations[key] || key;
</script>

<button
  id="openNotificationsSidebar"
  class="btn position-relative d-flex align-items-center justify-content-center rounded-3"
  style="width:48px;height:48px;border:none;background:transparent;font-family: 'Cairo', sans-serif;">
<i class="fas fa-bars text-primary"></i>
  <span id="notificationBadge" class="position-absolute d-flex align-items-center justify-content-center
               text-white fw-bold rounded-pill"
        style="top:-4px;right:-4px;
               min-width:22px;height:22px;
               background:linear-gradient(to right,#f59e0b,#f97316);
               border:2px solid #fff;font-size:12px;">
    0
  </span>
</button>

<div id="notificationsSidebar" class="notifications-sidebar">
  <div class="d-flex align-items-center justify-content-between mb-3" style="height: 60px; position: relative;">
    <div class="d-flex align-items-center gap-2 position-relative flex-grow-1">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" 
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
           class="w-6 h-6 flex-shrink-0" style="color: hsl(160, 84%, 39%);">
        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
      </svg>

      <div class="d-flex flex-column flex-grow-1">
        <h2 class="text-light mb-0" style="font-size: 1.25rem;font-family: 'Cairo', sans-serif;">@lang('notifications.notifications')</h2>
        <span class="text-muted small" id="notificationSummary" style="font-family: 'Cairo', sans-serif;">0 @lang('notifications.unread') 路 0 @lang('notifications.total')</span>
      </div>
    </div>

    <button id="closeSidebar" class="btn btn-sm btn-light d-flex align-items-center justify-content-center flex-shrink-0"
            style="border-radius:50%; width:32px; height:32px; padding:0;">
      <i class="fas fa-times" style="font-size:0.9rem;"></i>
    </button>
  </div>

  <div class="position-relative w-100 mb-3">
      <i class="fas fa-search text-light position-absolute" 
         style="top: 50%; transform: translateY(-50%); inset-inline-end: 15px;"></i>
      <input id="notificationSearch"
             type="text"
             style="border-radius: 20px; border:none; padding-inline-end: 40px; padding-inline-start: 15px;font-family: 'Cairo', sans-serif;" 
             placeholder="@lang('notifications.search_placeholder')"
             class="w-100 py-2 search-input text-light">
  </div>

  <div class="d-flex gap-2 mb-3 ">
    <button class="filter-btn active px-3" data-filter="all" style="font-family: 'Cairo', sans-serif;">@lang('notifications.all')</button>
    <button class="filter-btn" data-filter="pending" style="font-family: 'Cairo', sans-serif;">@lang('notifications.pending')</button>
    <button class="filter-btn" data-filter="pending" style="font-family: 'Cairo', sans-serif;">@lang('notifications.seen')</button>
    <button class="filter-btn" data-filter="approved" style="font-family: 'Cairo', sans-serif;">@lang('notifications.approved')</button>
    <button class="filter-btn" data-filter="rejected" style="font-family: 'Cairo', sans-serif;">@lang('notifications.rejected')</button>
  </div>

  <ul id="notificationList" class="list-unstyled mb-0">
  </ul>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
function getNotificationTitle(notification) {
  // Show Arabic title if lang is ar and exists
  if (lang === 'ar' && notification.title_ar) {
    return notification.title_ar;
  }
  if (typeof notification.title === 'object') {
    if (notification.title?.notification?.title) {
      return notification.title.notification.title;
    }
    if (notification.title?.data?.title) {
      return notification.title.data.title;
    }
  }
  if (typeof notification.title === 'string' && notification.title.trim()) {
    return notification.title;
  }
  return 'New Notification';
}

function getNotificationBody(notification) {
 
  if (lang === 'ar' && notification.body_ar) {
    return notification.body_ar;
  }
  if (typeof notification.title === 'object' && notification.title?.data?.body) {
    return notification.title.data.body;
  }
  if (notification.body && notification.body.trim()) {
    return notification.body;
  }
  return '';
}

function timeAgo(date) {
  const locale =  document.documentElement.lang || 'en';
  const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });

  const now = new Date();
  const d = new Date(date);
  const diff = (d - now) / 1000;

  const seconds = Math.round(diff);
  const minutes = Math.round(seconds / 60);
  const hours = Math.round(minutes / 60);
  const days = Math.round(hours / 24);

  if (Math.abs(seconds) < 60) return rtf.format(seconds, 'second');
  if (Math.abs(minutes) < 60) return rtf.format(minutes, 'minute');
  if (Math.abs(hours) < 24) return rtf.format(hours, 'hour');
  return rtf.format(days, 'day');
}

function renderNotificationCard(notification) {
  const typeMap = {
    'App\\Notifications\\BookingNotification': {
      icon: 'fas fa-user-clock',
      color: '',
      badge: t('reservation'),
      style: "color: rgb(165, 66, 215);",
      buttons: (notification.notification_status === 'pending' && notification.data?.booking_id)
        ? `<div class="d-flex gap-2 mb-2" style="max-height:30px;">
            <button class="btn btn-success flex-fill action-btn d-flex align-items-center justify-content-center gap-2"
                    style="border-radius:15px;"
                    data-booking-id="${notification.data.booking_id || ''}"
                    data-notification-id="${notification.id || ''}"
                    data-type="booking"
                    data-action="approved">
              <i class="fas fa-check"></i> ${t('approve')}
            </button>
            <button class="btn btn-danger flex-fill action-btn d-flex align-items-center justify-content-center gap-2"
                    style="border-radius:15px;"
                    data-booking-id="${notification.data.booking_id || ''}"
                    data-notification-id="${notification.id || ''}"
                    data-type="booking"
                    data-action="rejected">
              <i class="fas fa-times"></i> ${t('reject')}
            </button>
          </div>`
        : `<div>
            <span class="badge ${notification.notification_status === 'approved' ? 'bg-success' : 'btn-danger'}" style="font-size:0.85rem;">
              ${notification.actionBy ?? notification.notification_status}
            </span>
          </div>`
    },
    'approveJopOrder': {
      icon: 'fas fa-tools text-primary',
      color: '',
      badge: t('jobOrder'),
      style: "color: rgb(34, 197, 94);",
      buttons: ''
    },
    'SparePartsAddedToJobOrder': {
      icon: 'fas fa-box ',
      color: '',
      badge: 'industry',
      style: "color: rgb(226, 54, 112);",
      buttons: ''
    },
    'default': {
      icon: 'fas fa-bell',
      color: 'text-muted',
      badge: t('info'),
      buttons: ''
    }
  };

  const typeData = typeMap[notification.type] || typeMap.default;
  const notificationTitle = getNotificationTitle(notification);
  const notificationBody = getNotificationBody(notification);

  return `
    <div class="d-flex gap-3 align-items-start p-2" style="background: rgba(255,255,255,0.05); border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
      <div class="flex-shrink-0 d-flex align-items-center justify-content-center" style="width:50px; height:50px; border-radius:50%;">
        <i class="${typeData.icon} ${typeData.color} fa-2x" style="${typeData.style || ''}"></i>
      </div>
      <div class="flex-grow-1 d-flex flex-column" style="direction: ${lang === 'ar' ? 'rtl' : 'ltr'};">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <span class="badge rounded-pill border">${typeData.badge}</span>
          <small class="text-light">${notification.data?.created_at ? timeAgo(notification.data.created_at) : notification.time || t('justNow')}</small>
        </div>
        <h5 style="
          font-family: 'Cairo', sans-serif;
          font-weight: 600;
          color: #f8f9fa;
          margin-bottom: 4px;">
          ${notificationTitle}
        </h5>
        <p class="text-light mb-1" style="font-size:0.9rem; direction: ${lang === 'ar' ? 'rtl' : 'ltr'};">
          ${notificationBody}
          ${notification.contact_name ? `<br><strong>${t('customer')}:</strong> ${notification.contact_name}` : ''}
          ${notification.device_name ? `<br><strong>${t('device')}:</strong> ${notification.device_name}` : ''}
          ${notification.location_name ? `<br><strong>${t('location')}:</strong> ${notification.location_name}` : ''}
          ${notification.note ? `<br><strong>${t('note')}:</strong> ${notification.note}` : ''}
        </p>
        ${typeData.buttons || ''}
      </div>
    </div>
  `;
}

function attachActionHandlers(element, notification) {
  element.querySelectorAll('.action-btn').forEach(btn => {
    btn.onclick = async (event) => {
      event.preventDefault();
      
      const bookingId = btn.dataset.bookingId;
      const action = btn.dataset.action;
      const notificationId = btn.dataset.notificationId;
      const type = btn.dataset.type;
      
      if (!bookingId || !notificationId) {
        console.error('Missing booking or notification ID');
        return;
      }
      
      
      try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
          console.error('CSRF token not found');
          return;
        }
        
        const response = await fetch('{{ route("notification.action") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({
            booking_id: bookingId,
            action: action,
            type: type,
            notification_id: notificationId,
          })
        });

        console.log('Action response:', response.status);
        const data = await response.json();
        console.log('Action result:', data);

        if (response.ok && data.success) {
          console.log('Booking action successful');
          element.remove();
          fetchNotificationCount();
        } else {
          console.error('Failed to update booking:', data);
          alert('Failed to ' + action + ' booking');
        }
      } catch (err) {
        console.error('Error sending action:', err);
        alert('Error: ' + err.message);
      }
    };
  });
}
async function GetNotificationBystatus(status) {
  const list = document.getElementById('notificationList');
  if (!list) return;

list.innerHTML = '';
  try {
    const res = await fetch(`/notifications/by-status?status=${status}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const json = await res.json();
    console.log('Notifications data received:', json);
    
    if (!json.success) {
      console.warn('API returned success: false');
      list.innerHTML = `<li class="text-center text-warning py-5">${notificationTranslations['no_data_server']}</li>`;
      return;
    }

    const notificationsData = json.data || [];
    console.log('Processing', notificationsData.length, 'notifications');

    if (notificationsData.length === 0) {
      list.innerHTML = `<li class="text-center text-muted py-5">${t('noNotifications')}</li>`;
      return;
    }

    notificationsData.forEach((n, index) => {
      console.log('Rendering notification:', index, n.id, n.type);
      
      const li = document.createElement('li');
      li.className = 'list-group-item mb-3 p-3 glass-notification';
      li.dataset.notificationId = n.id;
      li.dataset.notificationId = n.id;
      li.dataset.status = n.notification_status || 'pending';
      
      const titleText = typeof n.title === 'object' 
        ? (n.title?.notification?.title || n.title?.data?.body || 'Notification')
        : (n.title || 'Notification');
      
      li.dataset.text = titleText.toLowerCase();
      li.dataset.day = n.day || 'today';
      
      if (n.unread) li.classList.add('unread');
      
      li.innerHTML = renderNotificationCard(n);
      list.appendChild(li);
      
      attachActionHandlers(li, n);
    });
    
    console.log('All notifications rendered successfully');
  } catch (error) {
    console.error('Error loading notifications:', error);
    const list = document.getElementById('notificationList');
    if (list) {
      list.innerHTML = `<li class="text-center text-danger py-5">${t('errorLoading')}: ${error.message}</li>`;
    }
  }
}
async function loadNotifications(reset = false) {
  const list = document.getElementById('notificationList');
  
  if (!list) {
    console.error('notificationList element not found');
    return;
  }
  
  if (reset) list.innerHTML = '';

  try {
    
    const res = await fetch('/notifications/list', { 
      method: 'GET',
      headers: { 
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      } 
    });
    
    console.log('Response status:', res.status, res.statusText);
    
    if (!res.ok) {
      console.error('Failed to fetch notifications:', res.status, res.statusText);
      list.innerHTML = `<li class="text-center text-danger py-5">${t('errorLoading')}</li>`;
      return;
    }
    
    const json = await res.json();
    console.log('Notifications data received:', json);
    
    if (!json.success) {
      console.warn('API returned success: false');
      list.innerHTML = `<li class="text-center text-warning py-5">${t('noDataServer')}</li>`;
      return;
    }

    const notificationsData = json.data || [];
    console.log('Processing', notificationsData.length, 'notifications');

    if (notificationsData.length === 0) {
      list.innerHTML = `<li class="text-center text-muted py-5">${t('noNotifications')}</li>`;
      return;
    }

    notificationsData.forEach((n, index) => {
      console.log('Rendering notification:', index, n.id, n.type);
      
      const li = document.createElement('li');
      li.className = 'list-group-item mb-3 p-3 glass-notification';
      li.dataset.notificationId = n.id;
      li.dataset.status = n.notification_status || 'pending';
      
      const titleText = typeof n.title === 'object' 
        ? (n.title?.notification?.title || n.title?.data?.body || 'Notification')
        : (n.title || 'Notification');
      
      li.dataset.text = titleText.toLowerCase();
      li.dataset.day = n.day || 'today';
      
      if (n.unread) li.classList.add('unread');
      
      li.innerHTML = renderNotificationCard(n);
      list.appendChild(li);
      
      attachActionHandlers(li, n);
    });
    
    console.log('All notifications rendered successfully');
  } catch (error) {
    console.error('Error loading notifications:', error);
    const list = document.getElementById('notificationList');
    if (list) {
      list.innerHTML = `<li class="text-center text-danger py-5">${t('errorLoading')}: ${error.message}</li>`;
    }
  }
}

async function fetchNotificationCount() {
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
      console.warn('CSRF token not found');
      return;
    }

    const response = await fetch('/notifications/count', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken
      }
    });

    if (!response.ok) {
      console.error('Failed to fetch notifications count:', response.status);
      return;
    }

    const data = await response.json();
    console.log('Notification count:', data.unread_count);

    const notifBadge = document.getElementById('notificationBadge'); 
    if (notifBadge) {
      notifBadge.textContent = data.unread_count || 0; 
    }

    const notifSummary = document.getElementById('notificationSummary');
    if (notifSummary) {
      notifSummary.textContent = `${data.unread_count || 0} ${notificationTranslations['unread']} 路 ${data.total_count || 0} ${notificationTranslations['total']}`;
    }

  } catch (error) {
    console.error('Error fetching notifications count:', error);
  }
}

document.addEventListener('DOMContentLoaded', function() {
  console.log('Notification system initializing...');
  
  const sidebar = document.getElementById('notificationsSidebar');
  const openBtn = document.getElementById('openNotificationsSidebar');
  const closeBtn = document.getElementById('closeSidebar');
  const search = document.getElementById('notificationSearch');
  const filters = document.querySelectorAll('.filter-btn');
  const list = document.getElementById('notificationList');

  let currentFilter = 'all';

  if (!sidebar) console.error('notificationsSidebar not found');
  if (!openBtn) console.error('openNotificationsSidebar not found');
  if (!closeBtn) console.error('closeSidebar not found');
  if (!search) console.error('notificationSearch not found');
  if (!list) console.error('notificationList not found');
  
  if (!sidebar || !openBtn || !closeBtn || !search || !list) {
    console.error('Required notification elements not found');
    return;
  }
  
  console.log('All elements found, setting up event listeners...');

 openBtn.onclick = () => {
  sidebar.classList.add('active');
  loadNotifications(true);
};
  closeBtn.onclick = async () => {
  sidebar.classList.remove('active');
  await markAllNotificationsAsRead();
};
async function markAllNotificationsAsRead() {
  try {
    const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute('content');

    if (!csrfToken) return;

    const notificationList = document.getElementById('notificationList');
    if (!notificationList) return;

    const unreadIds = Array.from(notificationList.querySelectorAll('.glass-notification.unread'))
      .map(li => li.dataset.notificationId) 
      .filter(id => id); 

    if (unreadIds.length === 0) return;

    const res = await fetch('/notifications/mark-all-read', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({ ids: unreadIds }) 
    });

    if (res.ok) {
      const data = await res.json();
      console.log('Mark all read response:', data);

      fetchNotificationCount();

      unreadIds.forEach(id => {
        const li = notificationList.querySelector(`.glass-notification[data-notification-id="${id}"]`);
        if (li) li.classList.remove('unread');
      });
    } else {
      console.error('Failed to mark notifications as read', res.statusText);
    }
  } catch (error) {
    console.error('Error marking all notifications as read:', error);
  }
}
 document.addEventListener('click', async e => {
  if (!sidebar.contains(e.target) && 
      !e.target.closest('#openNotificationsSidebar') &&
      sidebar.classList.contains('active')) {

    sidebar.classList.remove('active');
    await markAllNotificationsAsRead();
  }
});

  function applyFilter() {
    const value = search.value.toLowerCase();

    document.querySelectorAll('.glass-notification').forEach(item => {
      const matchStatus =
        currentFilter === 'all' || item.dataset.status === currentFilter;

      const matchText = item.dataset.text.includes(value);

      item.style.display = matchStatus && matchText ? '' : 'none';
    });
  }

  search.oninput = applyFilter;
  
filters.forEach(btn => {
  btn.onclick = () => {

    filters.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const status = btn.dataset.filter;

    if (status === 'all') {
      loadNotifications(true);
    } else {
      GetNotificationBystatus(status);
    }
  };
});


  console.log('Fetching initial notification count...');
  fetchNotificationCount();
});
</script>

<script type="module">
  import { initializeApp } from "https://www.gstatic.com/firebasejs/12.8.0/firebase-app.js";
  import { getMessaging, onMessage } from "https://www.gstatic.com/firebasejs/12.8.0/firebase-messaging.js";

  const firebaseConfig = {
  apiKey: "{{ session('notification_settings.firebase_api_key') }}",
  authDomain: "{{ session('notification_settings.firebase_auth_domain') }}",
  projectId: "{{ session('notification_settings.firebase_project_id') }}",
  storageBucket: "{{ session('notification_settings.firebase_storage_bucket') }}",
  messagingSenderId: "{{ session('notification_settings.firebase_messaging_sender_id') }}",
  appId: "{{ session('notification_settings.firebase_app_id') }}",
  measurementId: "{{ session('notification_settings.firebase_measurement_id') }}"
};

  try {
    const app = initializeApp(firebaseConfig);
    const messaging = getMessaging(app);
    console.log('Firebase initialized successfully');

    onMessage(messaging, async (payload) => {
      console.log('New FCM notification received:', payload);
      
      const list = document.getElementById('notificationList');
      const data = payload.data || {};
      
      const li = document.createElement('li');
      li.className = 'list-group-item mb-3 p-3 glass-notification';
      li.dataset.status = data.notification_status || 'pending';
      li.dataset.text = ((data.title || payload.notification?.title) || 'Notification').toLowerCase();
      
      const typeMap = {
        'App\\Notifications\\BookingNotification': {
          icon: 'fas fa-user-clock',
          color: '',
          badge: t('reservation'),
          style: "color: rgb(165, 66, 215);",
        },
        'approveJopOrder': {
          icon: 'fas fa-tools text-primary',
          color: '',
          badge: t('jobOrder'),
          style: "color: rgb(34, 197, 94);",
        },
        'default': {
          icon: 'fas fa-bell',
          color: 'text-muted',
          badge: t('info'),
        }
      };
      
      const typeData = typeMap[data.type] || typeMap.default;
      
      li.innerHTML = `
        <div class="d-flex gap-3 align-items-start p-2" style="background: rgba(255,255,255,0.05); border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
          <div class="flex-shrink-0 d-flex align-items-center justify-content-center" style="width:50px; height:50px; border-radius:50%; ">
            <i class="${typeData.icon} ${typeData.color} fa-2x" style="${typeData.style || ''}"></i>
          </div>
          <div class="flex-grow-1 d-flex flex-column" style="direction: ${lang === 'ar' ? 'rtl' : 'ltr'};">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="badge rounded-pill border">${typeData.badge}</span>
              <small class="text-light">${new Date().toLocaleString()}</small>
            </div>
            <h3 class="fw-semibold text-light mb-1">${payload.notification?.title || 'New Notification'}</h3>
            <p class="text-light mb-1" style="font-size:0.9rem;">
              ${payload.notification?.body || data.body || ''}
            </p>
          </div>
        </div>
      `;
      
      if (list) {
        list.insertBefore(li, list.firstChild);
      }

      try {
        const response = await fetch('/notifications/count', {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          }
        });

        if (response.ok) {
          const countData = await response.json();
          const notifBadge = document.getElementById('notificationBadge'); 
          if (notifBadge) notifBadge.textContent = countData.unread_count || 0;

          const notifSummary = document.getElementById('notificationSummary');
          if (notifSummary) {
            notifSummary.textContent = `${countData.unread_count || 0} ${t('unread')}`;
          }
        }
      } catch (error) {
        console.error('Error fetching notifications count:', error);
      }
    });
  } catch (error) {
    console.error('Firebase initialization error:', error);
  }

  document.addEventListener('click', async function(e) {
  if (e.target.closest('.delete-notification')) {
    const btn = e.target.closest('.delete-notification');
    const notificationId = btn.dataset.notificationId;
    if (!notificationId) return;

    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const res = await fetch(`/notifications/delete/${notificationId}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        }
      });

      const data = await res.json();

      if (data.success) {
        btn.closest('li').remove(); 
        fetchNotificationCount();
      } else {
        alert(data.msg);
      }
    } catch (err) {
      console.error(err);
      alert('Error deleting notification');
    }
  }
});

</script>


<style>

.notifications-sidebar {
    font-family: 'Cairo', sans-serif;
    position: fixed;
    top: 0;
    right: calc(-15% - 400px);
    width: 420px;
    height: 100vh;
    padding: 1rem;
    background: rgba(29, 13, 13, .65);
    backdrop-filter: blur(18px);
    box-shadow: -20px 0 40px rgba(0, 0, 0, .25);
    transition: right .35s ease;
    z-index: 1055;
    overflow-y: auto;
    direction: ltr;
}

.notifications-sidebar.active { right: 0; }

:root[dir="rtl"] .notifications-sidebar,
[dir="rtl"] .notifications-sidebar,
html[lang="ar"] .notifications-sidebar {
  right: auto;
  left: calc(-10% - 420px);
  box-shadow: 20px 0 40px rgba(0,0,0,.25);
  direction: rtl;
}

:root[dir="rtl"] .notifications-sidebar.active,
[dir="rtl"] .notifications-sidebar.active,
html[lang="ar"] .notifications-sidebar.active {
  left: 0;
}

.search-input {
  background: rgba(255,255,255,.1);
  border: none;
  color: #fff;
}

.search-input::placeholder {
  color: rgba(255,255,255,.6);
}

.day-title {
  font-size:22px;
  color: #f0efefff;
  margin: 10px 0 4px;
}

.filter-container {
  display: flex;
  gap: 0.5rem;
  max-width: 100%;
  flex-wrap: wrap;
}

.filter-btn {
  flex: 1;
  border: none;
  border-radius: 10px;
  padding: 6px 12px;
  background: rgba(255,255,255,.1);
  color: #fff;
  font-family: 'Cairo', sans-serif;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: fit-content;
}

.filter-btn:hover {
  background: rgba(255,255,255,.15);
}

.filter-btn.active {
  background: hsl(160, 84%, 39%);
  color: #000;
  font-weight: 600;
}

.glass-notification {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: 15px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    width: 100%;
    transition: all 0.3s ease;
}
.glass-notification:hover {
    box-shadow: 0 8px 40px rgba(0,0,0,0.2);
    transform: translateY(-2px);
}

.glass-card {
    backdrop-filter: blur(10px);
    background: rgba(33, 38, 49, 0.6);
    border: 1px solid hsl(var(--glass-border));
    border-radius: 12px;
}

@media (max-width:735px) {
  .notifications-sidebar {
    width: 100vw !important;
       right: calc(-15% - 400px);
  }
  
  :root[dir="rtl"] .notifications-sidebar,
  [dir="rtl"] .notifications-sidebar,
  html[lang="ar"] .notifications-sidebar {
      left: calc(-10% - 420px) ;
    right: auto;
  }
  
  .filter-btn {
    flex: 0 1 calc(50% - 4px);
  }
}

@media (max-width:480px) {
  .notifications-sidebar {
    width: 100vw !important;
    padding: 0.75rem;
  }
  
  .filter-btn {
    flex: 0 1 calc(50% - 4px);
    font-size: 0.85rem;
    padding: 4px 8px;
  }
}
</style>

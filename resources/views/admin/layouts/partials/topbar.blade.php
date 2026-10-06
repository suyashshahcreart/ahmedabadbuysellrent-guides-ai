<header>
     <div class="topbar">
     <div class="container-fluid">
          <div class="navbar-header">
               <div class="d-flex align-items-center gap-2">
                    <div class="topbar-item">
                         <button type="button" class="button-toggle-menu topbar-button">
                              <i class="ri-menu-2-line fs-24"></i>
                         </button>
                    </div>
               </div>
               <div class="d-flex align-items-center gap-1">
                    <div class="topbar-item">
                         <button type="button" class="topbar-button" id="light-dark-mode">
                              <i class="ri-moon-line fs-24 light-mode"></i>
                              <i class="ri-sun-line fs-24 dark-mode"></i>
                         </button>
                    </div>
                    <div class="dropdown topbar-item d-none d-lg-flex">
                         <button type="button" class="topbar-button" data-toggle="fullscreen">
                              <i class="ri-fullscreen-line fs-24 fullscreen"></i>
                              <i class="ri-fullscreen-exit-line fs-24 quit-fullscreen"></i>
                         </button>
                    </div>
                    <div class="topbar-item d-none d-md-flex">
                         <button type="button" class="topbar-button" id="theme-settings-btn" data-bs-toggle="offcanvas" data-bs-target="#theme-settings-offcanvas" aria-controls="theme-settings-offcanvas">
                              <i class="ri-settings-4-line fs-24"></i>
                         </button>
                    </div>
                    <div class="dropdown topbar-item">
                         <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <span class="d-flex align-items-center">
                                   <img class="rounded-circle" width="32" height="32" src="{{ auth()->user()->profile_photo_url }}" alt="Profile">
                              </span>
                         </a>
                         <div class="dropdown-menu dropdown-menu-end">
                              <h6 class="dropdown-header">Welcome, {{ auth()->user()->name }}</h6>
                              <a class="dropdown-item" href="{{ url('/administrator/profile') }}">
                                   <i class="ri-user-settings-line align-middle me-2 fs-18"></i><span class="align-middle">My profile</span>
                              </a>
                              @can('insights.view')
                              <a class="dropdown-item" href="{{ route('administrator.insights.index') }}">
                                   <i class="ri-line-chart-line align-middle me-2 fs-18"></i><span class="align-middle">Insights</span>
                              </a>
                              @endcan
                              @canany(['faqs.view','faq.manage'])
                              <a class="dropdown-item" href="{{ route('administrator.faqs.index') }}">
                                   <i class="ri-questionnaire-line align-middle me-2 fs-18"></i><span class="align-middle">FAQs</span>
                              </a>
                              @endcanany
                              @canany(['media.view','media.manage'])
                              <a class="dropdown-item" href="{{ route('administrator.media.index') }}">
                                   <i class="ri-image-line align-middle me-2 fs-18"></i><span class="align-middle">Media</span>
                              </a>
                              @endcanany
                              @can('menus.view')
                              <a class="dropdown-item" href="{{ url('/administrator/menus') }}">
                                   <i class="ri-menu-line align-middle me-2 fs-18"></i><span class="align-middle">Navigation</span>
                              </a>
                              @endcan
                              @canany(['settings.view','settings.edit','settings.manage','settings.site','settings.sms','settings.mail','settings.theme','settings.post','settings.popup'])
                              <a class="dropdown-item" href="{{ route('administrator.settings.edit') }}">
                                   <i class="ri-settings-3-line align-middle me-2 fs-18"></i><span class="align-middle">Settings</span>
                              </a>
                              @endcanany
                              <div class="dropdown-divider my-1"></div>
                              <a class="dropdown-item text-danger" href="{{ route('administrator.logout') }}" onclick="event.preventDefault(); document.getElementById('admin-logout-form').submit();">
                                   <iconify-icon icon="solar:logout-3-broken" class="align-middle me-2 fs-18"></iconify-icon><span class="align-middle">Log out</span>
                              </a>
                         </div>
                    </div>
               </div>
          </div>
     </div></div>
</header>
<form id="admin-logout-form" action="{{ route('administrator.logout') }}" method="POST" class="d-none">@csrf</form>

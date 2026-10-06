@php
    $site = $site ?? \App\Support\SiteBranding::forView();
    $guidesActive = request()->routeIs('administrator.articles.*')
        || request()->routeIs('administrator.categories.*')
        || request()->routeIs('administrator.tags.*');
    $seoActive = request()->routeIs('administrator.seo.*') || request()->routeIs('administrator.redirects.*');
    $systemActive = request()->routeIs('administrator.users.*') || request()->routeIs('administrator.roles.*') || request()->routeIs('administrator.permissions.*');
    $aiActive = request()->routeIs('administrator.ai.*');
@endphp
<div class="main-nav">
     <div class="logo-box">
         <a href="{{ route('administrator.dashboard') }}" class="logo-dark" title="{{ $site['name'] }}">
              <img src="{{ $site['sidebar']['dark_sm'] }}" class="logo-sm" alt="{{ $site['name'] }}">
              <span class="logo-lg logo-brand">
                   <img src="{{ $site['sidebar']['dark_lg'] }}" class="logo-brand-img" alt="">
                   @if(!empty($site['show_name']))
                   <span class="logo-brand-text">{{ $site['name'] }}</span>
                   @endif
              </span>
          </a>
          <a href="{{ route('administrator.dashboard') }}" class="logo-light" title="{{ $site['name'] }}">
               <img src="{{ $site['sidebar']['light_sm'] }}" class="logo-sm" alt="{{ $site['name'] }}">
               <span class="logo-lg logo-brand">
                    <img src="{{ $site['sidebar']['light_lg'] }}" class="logo-brand-img" alt="">
                    @if(!empty($site['show_name']))
                    <span class="logo-brand-text">{{ $site['name'] }}</span>
                    @endif
               </span>
          </a>
     </div>
     <button type="button" class="button-sm-hover" aria-label="Show Full Sidebar">
          <i class="ri-menu-2-line fs-24 button-sm-hover-icon"></i>
     </button>
     <div class="scrollbar" data-simplebar>
          <ul class="navbar-nav" id="navbar-nav">
               <li class="menu-title">Menu</li>
               <li class="nav-item">
                    <a class="nav-link" href="{{ route('administrator.dashboard') }}">
                         <span class="nav-icon"><i class="ri-dashboard-2-line"></i></span>
                         <span class="nav-text">Dashboard</span>
                    </a>
               </li>
               @canany(['articles.view','articles.create','categories.view','categories.manage','tags.view','tags.manage'])
               <li class="nav-item">
                    <a class="nav-link menu-arrow {{ $guidesActive ? '' : 'collapsed' }}" href="#sidebarGuides" data-bs-toggle="collapse" role="button" aria-expanded="{{ $guidesActive ? 'true' : 'false' }}" aria-controls="sidebarGuides">
                         <span class="nav-icon"><i class="ri-article-line"></i></span>
                         <span class="nav-text">Guides</span>
                    </a>
                    <div class="collapse {{ $guidesActive ? 'show' : '' }}" id="sidebarGuides">
                         <ul class="nav sub-navbar-nav">
                              @canany(['articles.view','articles.create'])
                              <li class="sub-nav-item">
                                   <a class="sub-nav-link {{ request()->routeIs('administrator.articles.*') && request('status') !== 'trash' ? 'active' : '' }}" href="{{ route('administrator.articles.index') }}">All Guides</a>
                              </li>
                              <li class="sub-nav-item">
                                   <a class="sub-nav-link {{ request()->routeIs('administrator.articles.index') && request('status') === 'trash' ? 'active' : '' }}" href="{{ route('administrator.articles.index', ['status' => 'trash']) }}">Trash</a>
                              </li>
                              @endcanany
                              @canany(['categories.view','categories.manage'])
                              <li class="sub-nav-item">
                                   <a class="sub-nav-link {{ request()->routeIs('administrator.categories.*') ? 'active' : '' }}" href="{{ route('administrator.categories.index') }}">Categories</a>
                              </li>
                              @endcanany
                              @canany(['tags.view','tags.manage'])
                              <li class="sub-nav-item">
                                   <a class="sub-nav-link {{ request()->routeIs('administrator.tags.*') ? 'active' : '' }}" href="{{ route('administrator.tags.index') }}">Tags</a>
                              </li>
                              @endcanany
                         </ul>
                    </div>
               </li>
               @endcanany
               @canany(['pages.view','pages.manage'])
               <li class="nav-item">
                    <a class="nav-link" href="{{ route('administrator.pages.index') }}">
                         <span class="nav-icon"><i class="ri-pages-line"></i></span>
                         <span class="nav-text">Pages</span>
                    </a>
               </li>
               @endcanany
               @can('authors.view')
               <li class="nav-item">
                    <a class="nav-link" href="{{ route('administrator.authors.index') }}">
                         <span class="nav-icon"><i class="ri-user-star-line"></i></span>
                         <span class="nav-text">Authors</span>
                    </a>
               </li>
               @endcan
               @can('localities.view')
               <li class="nav-item">
                    <a class="nav-link" href="{{ route('administrator.localities.index') }}">
                         <span class="nav-icon"><i class="ri-map-pin-line"></i></span>
                         <span class="nav-text">Localities</span>
                    </a>
               </li>
               @endcan
               @canany(['users.view','users.manage','roles.view','permissions.view'])
               <li class="menu-title">System</li>
               @endcanany
               @canany(['users.view','users.manage'])
               <li class="nav-item {{ request()->routeIs('administrator.users.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('administrator.users.index') }}">
                         <span class="nav-icon"><i class="ri-user-line"></i></span>
                         <span class="nav-text">Users</span>
                    </a>
               </li>
               @endcanany
               @can('roles.view')
               <li class="nav-item {{ request()->routeIs('administrator.roles.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('administrator.roles.index') }}">
                         <span class="nav-icon"><i class="ri-id-card-line"></i></span>
                         <span class="nav-text">Roles</span>
                    </a>
               </li>
               @endcan
               @can('permissions.view')
               <li class="nav-item {{ request()->routeIs('administrator.permissions.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('administrator.permissions.index') }}">
                         <span class="nav-icon"><i class="ri-lock-2-line"></i></span>
                         <span class="nav-text">Permissions</span>
                    </a>
               </li>
               @endcan
               @canany(['ai.credits.view','ai.credits.manage','ai.automation.view','ai.automation.manage'])
               <li class="nav-item">
                    <a class="nav-link menu-arrow {{ $aiActive ? '' : 'collapsed' }}" href="#sidebarAi" data-bs-toggle="collapse" role="button" aria-expanded="{{ $aiActive ? 'true' : 'false' }}">
                         <span class="nav-icon"><i class="ri-magic-line"></i></span>
                         <span class="nav-text">AI</span>
                    </a>
                    <div class="collapse {{ $aiActive ? 'show' : '' }}" id="sidebarAi">
                         <ul class="nav sub-navbar-nav">
                              @canany(['ai.automation.view','ai.automation.manage'])
                              <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('administrator.ai.automation.index') }}">Automation</a></li>
                              <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('administrator.ai.automation.reports') }}">Reports</a></li>
                              @endcanany
                              @canany(['ai.credits.view','ai.credits.manage'])
                              <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('administrator.ai.credits.index') }}">Credits</a></li>
                              @endcanany
                         </ul>
                    </div>
               </li>
               @endcanany

               @canany(['seo.view','seo.manage','seo.redirects.view','redirects.manage'])
               <li class="nav-item">
                    <a class="nav-link menu-arrow {{ $seoActive ? '' : 'collapsed' }}" href="#sidebarSeo" data-bs-toggle="collapse" role="button" aria-expanded="{{ $seoActive ? 'true' : 'false' }}">
                         <span class="nav-icon"><i class="ri-search-line"></i></span>
                         <span class="nav-text">SEO Management</span>
                    </a>
                    <div class="collapse {{ $seoActive ? 'show' : '' }}" id="sidebarSeo">
                         <ul class="nav sub-navbar-nav">
                              @canany(['seo.view','seo.manage'])
                              <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('administrator.seo.meta.index') }}">SEO Meta</a></li>
                              @endcanany
                              @canany(['seo.templates.manage','seo.view'])
                              <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('administrator.seo.templates.index') }}">Templates</a></li>
                              @endcanany
                              @canany(['seo.redirects.view','redirects.manage'])
                              <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('administrator.redirects.index') }}">URL Redirects</a></li>
                              @endcanany
                              @can('seo.schemas.view')
                              <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('administrator.seo.schemas.index') }}">Schema Library</a></li>
                              @endcan
                              @canany(['seo.sitemap.view','seo.view'])
                              <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('administrator.seo.sitemap.index') }}">Sitemap</a></li>
                              @endcanany
                              @canany(['seo.view','seo.manage'])
                              <li class="sub-nav-item"><a class="sub-nav-link" href="{{ route('administrator.seo.audit') }}">SEO audit</a></li>
                              @endcanany
                         </ul>
                    </div>
               </li>
               @endcanany
               @can('activity.view')
               <li class="nav-item">
                    <a class="nav-link" href="{{ url('/administrator/activity') }}">
                         <span class="nav-icon"><i class="ri-history-line"></i></span>
                         <span class="nav-text">Activity</span>
                    </a>
               </li>
               @endcan
          </ul>
     </div>
</div>

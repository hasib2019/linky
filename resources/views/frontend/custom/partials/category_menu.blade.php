{{-- <div class="aiz-category-menu bg-white rounded-0 border-top" id="category-sidebar" style="width:270px;">
    <ul class="list-unstyled categories no-scrollbar mb-0 text-left">
        @foreach (get_level_zero_categories()->take(12) as $key => $category)
            @php
                $category_name = $category->getTranslation('name');
            @endphp
            <li class="category-nav-element border border-top-0" data-id="{{ $category->id }}">
                <a href="{{ route('products.category', $category->slug) }}"
                    class="text-truncate text-dark px-4 fs-14 d-block hov-column-gap-1">
                    <img class="cat-image lazyload mr-2 opacity-60" src="{{ static_asset('assets/img/placeholder.jpg') }}"
                        data-src="{{ isset($category->catIcon->file_name) ? my_asset($category->catIcon->file_name) : static_asset('assets/img/placeholder.jpg') }}" width="16" alt="{{ $category_name }}"
                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                    <span class="cat-name has-transition">{{ $category_name }}</span>
                </a>

                <div class="sub-cat-menu more c-scrollbar-light border p-4 shadow-none">
                    <div class="c-preloader text-center absolute-center">
                        <i class="las la-spinner la-spin la-3x opacity-70"></i>
                    </div>
                </div>

            </li>
        @endforeach
    </ul>
</div> --}}
<nav class="custom-side-nav" data-v-7cf6c81e=""><ul class="main-menu" data-v-7cf6c81e=""><li data-v-7cf6c81e=""><a href="/shop" class="menu-item" data-v-7cf6c81e="">All Products
</a></li> <li data-v-7cf6c81e=""><a href="/category/clothes?close=false" class="menu-item" data-v-7cf6c81e="">Clothes

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li><li data-v-7cf6c81e=""><a href="/category/nursing?close=false" class="menu-item" data-v-7cf6c81e="">Nursing

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li><li data-v-7cf6c81e=""><a href="/category/feeding?close=false" class="menu-item" data-v-7cf6c81e="">Feeding

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li><li data-v-7cf6c81e=""><a href="/category/diapers?close=false" class="menu-item" data-v-7cf6c81e="">Diapers

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li><li data-v-7cf6c81e=""><a href="/category/bath-skin?close=false" class="menu-item" data-v-7cf6c81e="">Bath &amp; Skin

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li><li data-v-7cf6c81e=""><a href="/category/health-safety?close=false" class="menu-item" data-v-7cf6c81e="">Health &amp; Safety

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li><li data-v-7cf6c81e=""><a href="/category/toys?close=false" class="menu-item" data-v-7cf6c81e="">Toys

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li><li data-v-7cf6c81e=""><a href="/category/gears?close=false" class="menu-item" data-v-7cf6c81e="">Gears

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li><li data-v-7cf6c81e=""><a href="/category/classy-home?close=false" class="menu-item" data-v-7cf6c81e="">Classy Home

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li><li data-v-7cf6c81e=""><a href="/category/birthday?close=false" class="menu-item" data-v-7cf6c81e="">Birthday

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li><li data-v-7cf6c81e=""><a href="/category/moms?close=false" class="menu-item" data-v-7cf6c81e="">Moms

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li><li data-v-7cf6c81e=""><a href="/category/winter?close=false" class="menu-item" data-v-7cf6c81e="">Winter

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li><li data-v-7cf6c81e=""><a href="/category/footwear?close=false" class="menu-item" data-v-7cf6c81e="">Footwear

  <i class="fa fa-plus float-right mr-2" data-v-7cf6c81e=""></i></a> <!----></li></ul></nav>
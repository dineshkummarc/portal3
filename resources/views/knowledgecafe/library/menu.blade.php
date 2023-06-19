<ul class="nav nav-pills">
    @php
     $request['all books'] = 'books';

     $wishlistSelected = request()->input('wishlist','active') === 'booksInWishlist';
     $borrowedSelected = request()->input('borrowedBook','active') === 'markedAsBorrowed';

     $allBooksSelected =  $active === 'books' && ! $wishlistSelected && ! $borrowedSelected;

    @endphp
        <a class="nav-item nav-link {{ $allBooksSelected ? 'active' : '' }}"  href="{{ route('books.index', $request) }}"><i class="fa fa-book"></i>&nbsp;Books</a>
    </li>

    @can('library_book_category.view')
    <li class="nav-item">
        <a class="nav-item nav-link {{ $active === 'book_category' ? 'active' : '' }}" href="{{ route('books.category.index') }}"><i class="fa fa-book"></i>&nbsp;Books Category</a>
    </li>
    @endcan

    <li class="nav-item">
        <a class="nav-item nav-link {{ $active === 'book_a_month' ? 'active' : '' }}" href="{{ route('book.book-a-month.index') }}"><i class="fa fa-book"></i>&nbsp;A Book A Month</a>
    </li>
    <li class="nav-item">
        @php
         $params = array_merge($request,  ['wishlist' => 'booksInWishlist']);
         $wishlistCount = auth()->user()->booksInWishlist->count();
        @endphp
        <a class="nav-item nav-link  {{ (request()->input('wishlist','active') === 'booksInWishlist') ? 'active' : '' }}"  href="{{ route('books.index', $params)  }}"><i class="fa fa-book"></i>&nbsp;Wish listed Books({{$wishlistCount}})</a>
    </li>
    <li class="nav-item">
        @php
          $params = array_merge($request,  ['borrowedBook' => 'markedAsBorrowed']);
          $borrowedCount = auth()->user()->booksBorrower->count();
        @endphp
        <a class="nav-item nav-link {{ (request()->input('borrowedBook','active') === 'markedAsBorrowed') ? 'active' : '' }}"  href="{{ route('books.index', $params)  }}"><i class="fa fa-book"></i>&nbsp;Borrowed Books({{$borrowedCount}})</a>
    </li>
</ul>

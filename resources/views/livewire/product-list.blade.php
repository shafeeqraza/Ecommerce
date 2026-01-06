<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <h1 class="text-3xl font-bold text-gray-900 mb-6">Products</h1>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($products as $product)
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $product->name }}</h3>
                        <p class="text-2xl font-bold text-blue-600 mb-2">${{ number_format($product->price, 2) }}</p>
                        <p class="text-sm text-gray-600 mb-4">
                            Stock: <span class="font-medium {{ $product->stock_quantity <= 5 ? 'text-red-600' : 'text-gray-900' }}">{{ $product->stock_quantity }}</span>
                        </p>
                        
                        @if($product->stock_quantity > 0)
                            <button 
                                wire:click="addToCart({{ $product->id }}, 1)"
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition"
                            >
                                Add to Cart
                            </button>
                        @else
                            <button disabled class="w-full bg-gray-400 text-white py-2 px-4 rounded cursor-not-allowed">
                                Out of Stock
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        </div>
    </div>
</div>

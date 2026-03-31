<?php



namespace Modules\Vendors\Entities;



use App\Product;

use App\Warranty;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Modules\CountryofOrigin\CountryofOrigin;



class VendorsProduct extends Model

{

    use HasFactory;
    
    protected $primaryKey = null;
    
    public $incrementing = false;



    protected $table = 'productstovendor';



    protected $fillable = [

        'product_id',

        'Vendor_id',             

        'Product_price',

        'warranty_id',

        'shipping_information',

        'Return_policy',

        'country_id',

        'created_at',

        'updated_at',

    ];



    protected $casts = [

        'created_at' => 'datetime',

        'updated_at' => 'datetime',

    ];



 

    public function product()

    {

        return $this->belongsTo(Product::class, 'product_id', 'id');

    }



       public function country()
    {
        return $this->belongsTo(CountryofOrigin::class, 'country_id', 'id');
    }


    public function warranty()

    {

        return $this->belongsTo(Warranty::class, 'warranty_id', 'id');

    }



//     public function vendor()

//     {

//         return $this->belongsTo(Vendor::class, 'Vendor_id', 'id'); // أو 'Vendor_id' لو في جدول vendors

//     }

}
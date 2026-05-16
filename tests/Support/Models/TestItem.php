<?php
namespace Tests\Support\Models;
use App\Traits\BelongsToTenant;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
/**
 * Hilfsmodel für Tests des BelongsToTenant Traits.
 * Nur in Tests verwendet — nie in Produktion.
 */
class TestItem extends Model
{
    use HasUlid, BelongsToTenant;
    protected $table = 'test_items';
    protected $fillable = ['tenant_id', 'name'];
}

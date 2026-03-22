<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credito extends Model
{
    protected $table = 'credito';
    protected $primaryKey = 'id_factura';
    public $timestamps = false;
    protected $fillable = [
        'id_factura',
        'serie_doc_modificado',
        'numero_doc_modificado',
        'fecha_creacion',
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'id_factura');
    }

    public function facturaModificada()
    {
        return $this->belongsTo(Factura::class, 'serie_doc_modificado', 'serie')
                    ->where('numero', $this->numero_doc_modificado);
    }
}

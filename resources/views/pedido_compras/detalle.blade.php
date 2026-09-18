<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title">Detalle Pedido</h3>

        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <div class="row mb-2">
            <!-- Botón para abrir el modal -->
            <div class="col-12 text-right">
                <button type="button" class="btn btn-primary" id="buscar" data-toggle="modal"
                    data-target="#productSearchModalPed">
                    <i class="fas fa-search"></i> Buscar Productos
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-bordered table-striped text-nowrap item-table">
                <thead class="thead-dark">
                    <tr class="text-center">
                        <th style="width:15%;">Código</th>
                        <th style="width:50%; min-width:200px;">Producto</th>
                        <th style="width:10%;">Cantidad</th>
                        <th style="width:10%;">Prec. Unit</th>
                        <th style="width:10%;">SubTotal</th>
                        <th style="width:5%;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="selectedProducts">
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold">
                        <td colspan="2" class="text-right">TOTAL ARTÍCULOS:</td>
                        <td id="totalCantidad" class="text-center">0</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div>
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h4 class="card-title">Add New Sub Caetogorys</h4>
            @if (session()->has('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @elseif ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif
            
            <form wire:submit="saveSubCategory">
                <div class="form-group">
                    <label>Category</label>
                    <select class="selectpicker form-control" data-live-search="true" wire:model.defer="ticket_category_id">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('ticket_category_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
              <div class="form-group">
                  <label>Name</label>
                  <input type="text" class="form-control"
                      placeholder="Category name"
                      wire:model.defer="name">
          
                  @error('name') <span class="text-danger">{{ $message }}</span> @enderror
              </div>
          
              <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                  <span wire:loading.remove wire:target="saveSubCategory">Submit</span>
                  <span class="d-none" wire:loading.class.remove="d-none">Saving... <i class="bx bx-loader-alt bx-spin fa-spin"></i></span>
              </button>
          </form>
          
          </div>
        </div>
      </div>
      <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <p class="card-title mb-0">All Category Tickets</p>
            <!-- Search Input -->
            <div class="row mb-3">
              <div class="col-md-12">
                <input 
                  type="text" 
                  class="form-control" 
                  id="searchCategory" 
                  placeholder="Search Category..." 
                  onkeyup="searchCategoryTable()"
                >
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-striped table-borderless" id="categoryTable">
                <thead>
                  <tr>
                    <th>Category Name</th>
                    <th>SubCategory Name</th>
                    <th>Active Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($subcategories as $subcategory)
                  <tr>
                    <td>{{ $subcategory->category->name }}</td>
                    <td>{{ $subcategory->name }}</td>
                    <td>
                      <span class="badge badge-success">{{ $subcategory->active ? 'Active' : 'Non Active' }}</span>
                    </td>
                    <td>
                      <button class="btn btn-secondary btn-sm {{ $subcategory->active ? 'btn-success' : 'btn-danger' }}" wire:click="changeStatusSubCategory({{ $subcategory->id }})">
                        {{ $subcategory->active ? 'Disable' : 'Enable' }}
                        <span class="d-none" wire:loading.class.remove="d-none">
                          <i class="bx bx-loader-alt bx-spin fa-spin"></i>
                        </span>
                      </button>
                    </td>
                  </tr>
                  @endforeach
                  @if ($subcategories->count() == 0)
                  <tr>
                    <td colspan="3" class="text-center">No data found</td>
                  </tr>
                  @endif
                </tbody>
                @if ($subcategories->count() > 0)
                <tfoot>
                  <tr>
                    <td colspan="3" class="text-center">{{ $subcategories->links() }}</td>
                  </tr>
                </tfoot>
              </table>
              @endif
            </div>
          </div>
        </div>
      </div>
      <script>
        function searchCategoryTable() {
          var input, filter, table, tr, td, i, txtValue;
          input = document.getElementById("searchCategory");
          filter = input.value.toUpperCase();
          table = document.getElementById("categoryTable");
          tr = table.getElementsByTagName("tr");
          // Start from i=1 to skip table header
          for (i = 1; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[0];
            if (td) {
              txtValue = td.textContent || td.innerText;
              if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
              } else {
                tr[i].style.display = "none";
              }
            }       
          }
        }
      </script>
</div>
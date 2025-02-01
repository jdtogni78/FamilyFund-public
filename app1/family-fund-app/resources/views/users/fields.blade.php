<!-- Name Field -->
<div class="form-group col-sm-6">
<label for="name">Name:</label>
<input type="text" name="name" class="form-control" maxlength="255">
</div>

<!-- Email Field -->
<div class="form-group col-sm-6">
<label for="email">Email:</label>
<input type="email" name="email" value="" class="form-control" maxlength="255]">
</div>

<!-- Email Verified At Field -->
<div class="form-group col-sm-6">
<label for="email_verified_at">Email Verified At:</label>
<input type="text" name="email_verified_at" class="form-control" id="email_verified_at">
</div>

@push('scripts')
   <script type="text/javascript">
           $('#email_verified_at').datetimepicker({
               format: 'YYYY-MM-DD HH:mm:ss',
               useCurrent: true,
               icons: {
                   up: "icon-arrow-up-circle icons font-2xl",
                   down: "icon-arrow-down-circle icons font-2xl"
               },
               sideBySide: true
           })
       </script>
@endpush


<!-- Password Field -->
<div class="form-group col-sm-6">
<label for="password">Password:</label>
<input type="password" name="password" value="['class' => 'form-control'" maxlength="255]">
</div>

<!-- Remember Token Field -->
<div class="form-group col-sm-6">
<label for="remember_token">Remember Token:</label>
<input type="text" name="remember_token" class="form-control" maxlength="100">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
</div>

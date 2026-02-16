@php
    $user_id = Auth::id();
    $credential = App\Models\ShiprocketCredential::where('user_id', $user_id)->first();
@endphp
<form class="form-horizontal" action="{{ route('shiprocket_settings.update') }}" method="POST" id="aizSubmitForm">
    @csrf
    <div id="shipRocket">
        <div class="form-group row">
            <input type="hidden" name="types[]" value="SHIPROCKET_EMAIL">
            <div class="col-md-3">
                <label class="col-from-label">{{translate('Shiprocket Email')}}</label>
            </div>
            <div class="col-md-9">
                <input type="text" class="form-control" name="SHIPROCKET_EMAIL" value="{{  $credential->email ?? '' }}"
                    placeholder="{{ translate('Shiprocket Email') }}">
            </div>
        </div>
        <div class="form-group row">
            <input type="hidden" name="types[]" value="SHIPROCKET_PASSWORD">
            <div class="col-md-3">
                <label class="col-from-label">{{translate('Shiprocket Password')}}</label>
            </div>
            <div class="col-md-9">
                <input type="text" class="form-control" name="SHIPROCKET_PASSWORD"
                    value="{{  $credential->password ?? ''}}" placeholder="{{ translate('Shiprocket Password') }}">
            </div>
        </div>
    </div>
    <div class="form-group mb-0 text-right">
        <button type="submit" class="btn btn-primary">{{translate('Save Configuration')}}</button>
    </div>
</form>
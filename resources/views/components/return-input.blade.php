@if($return = \App\Support\ListReturn::incomingReturn())
    <input type="hidden" name="return" value="{{ $return }}">
@endif

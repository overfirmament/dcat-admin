<div class="{{$viewClass['form-group']}}">

    <label class="{{$viewClass['label']}} control-label">{!! $label !!}</label>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')

        <textarea name="{{$name}}" class="form-control {{$class}}" rows="{{ $rows }}"
                  placeholder="{{ $placeholder }}" {!! $attributes !!} >{{ $value }}</textarea>

        <span class="help-block">
            @php
                $count = mb_strlen($value);
            @endphp
            @if($message)
                <i class="fa feather icon-help-circle"></i>&nbsp;{!! $message !!}
            @endif
                <span class="limit_count">({{$count }}/{{$limit}})</span>
        </span>

    </div>
</div>

<script init="{!! $selector !!}" >
    $('#'+id).on('input', function () {
        var length = $(this).val().length;
        var lengthText = length;
        if (length > {{$limit}}) {
            lengthText = '<font color="red">'+ length + '<\/font>';
        }
        $('#'+id).siblings('span.help-block').find('.limit_count').html("("+lengthText+"/{{$limit}})")
    });
</script>
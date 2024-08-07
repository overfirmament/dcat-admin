<div class="{{$viewClass['form-group']}}">

    <label class="{{$viewClass['label']}} control-label">{!! $label !!}</label>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')

        <textarea name="{{$name}}" class="form-control {{$class}}" rows="{{ $rows }}"
                  placeholder="{{ $placeholder }}" {!! $attributes !!} >{{ $value }}</textarea>

        <span class="help-block">
            @if($help)
                <i class="fa {{ \Illuminate\Support\Arr::get($help, 'icon') }}"></i>&nbsp;{!! \Illuminate\Support\Arr::get($help, 'text') !!}
            @endif
            @php
                $count = mb_strlen($value);
            @endphp
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
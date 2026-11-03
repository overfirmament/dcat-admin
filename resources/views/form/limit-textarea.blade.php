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
            <span class="limit_count" aria-live="polite">({{ mb_strlen((string) $value) }}/{{ $limit }})</span>
        </span>

    </div>
</div>

<script init="{!! $selector !!}">
    $this.on('input', function () {
        var length = Array.from($this.val() || '').length;
        var lengthText = length > {{ $limit }}
            ? '<span class="text-danger">' + length + '</span>'
            : length;

        $this.siblings('span.help-block')
            .find('.limit_count')
            .html('(' + lengthText + '/{{ $limit }})');
    });
</script>

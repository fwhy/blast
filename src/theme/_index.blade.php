<h1>Page list</h1>
<ul data-role="listview"
    data-view="table"
    data-select-node="true"
    data-structure='{"name": true, "date": true}'>

    @foreach($pages as $n => $p)
        @continue(!str_ends_with($n, '/'))
        @php($icon = $p->updateDate ? 'mif-file-text' : 'mif-folder')
        <li data-icon="<span class='{{$icon}}'>"
            data-caption="{{$p->rawSlug}}"
            data-name="{{$p->title}}"
            data-date="{{$p->updateDate?->format('Y-m-d')}}"
            onclick="location.href='{!! $p->slug !!}';"
        >
        </li>
    @endforeach
</ul>

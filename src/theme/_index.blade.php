<h1>Page list</h1>
<ul data-role="listview"
    data-view="table"
    data-select-node="true"
    data-structure='{"name": true, "date": true}'>

    @foreach($pages as $n => $p)
        @continue(!str_ends_with($n, '/'))
        <li data-icon="<span class='mif-file-code'>"
            data-caption="{{$p->rawSlug}}"
            data-name="{{$p->title}}"
            data-date="{{$p->updateDate?->format('Y-m-d')}}"
            onclick="location.href='{!! $p->slug !!}';"
        >
        </li>
    @endforeach
</ul>

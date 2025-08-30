
<div class="container">
    @if(!empty($report->report_url))
  <p>📄 Full MOSS report: <a href="{{ $report->report_url }}" target="_blank" rel="noopener">Open</a></p>
    @endif

    <p>Similarity: {{ round($report->similarity_percentage, 2) }}%</p>

    @if($matches = json_decode($report->matches, true))
    @if(count($matches))
        <table>
        <thead>
            <tr>
            <th>File 1</th><th>%</th><th>File 2</th><th>%</th><th>Lines</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matches as $m)
            <tr>
                <td><a href="{{ $m['file1_link'] ?? '#' }}" target="_blank">{{ $m['file1'] ?? '' }}</a></td>
                <td>{{ $m['p1'] ?? '' }}</td>
                <td><a href="{{ $m['file2_link'] ?? '#' }}" target="_blank">{{ $m['file2'] ?? '' }}</a></td>
                <td>{{ $m['p2'] ?? '' }}</td>
                <td>{{ $m['lines'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
        </table>
    @else
        <p>🔹 No detailed matches were extracted. Try opening the full report above.</p>
    @endif
    @endif
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="btn btn-danger">Logout</button>
</form>
</div>

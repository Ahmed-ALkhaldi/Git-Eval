
<div class="container">
    <h3>📄 Plagiarism Report</h3>

    <p><strong>Similarity:</strong> {{ $report->similarity_percentage }}%</p>

    <h4>🔹 Matches:</h4>
    <ul>
        @foreach($matches as $match)
            <li>
                <a href="{{ $match['link'] }}" target="_blank">{{ $match['file'] }}</a>
                - {{ $match['percentage'] }}%
            </li>
        @endforeach
    </ul>
</div>

<div style="margin: 20px">
	<form action="index.php" method="GET" class="form-inline">
		<input type="hidden" name="action" value="queue_query_for_propagation">
		<center>
			<div class="splash_main">
				<div>
					Enter query to propagate:
				</div>
				<textarea id="propagate_query" name="propagate_query"><?php echo $propagate_query ?></textarea>
			</div>
		</center>
		<br/>
		<center>
			<div class="splash_main">
				<div>
					Comments:
				</div>
				<textarea id="propagate_query_comments" name="propagate_query_comments"><?php echo $propagate_query_comments ?></textarea>
			</div>
		</center>
		<br/>
		<center>
			<div>
				<input class="btn-primary btn-large" type="submit" value="Propagate" name="submit">
			</div>
		</center>
	</form>
</div>

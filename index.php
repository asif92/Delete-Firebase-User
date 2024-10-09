<?php
$url = (parse_url($_SERVER['REQUEST_URI'])['path']);
$param = explode('/', $url);

$requested_project_id = $param[1];

$project_id = null;
$storage_bucket = null;
$app_id = null;
$api_key = null;

$search_results = glob('./uploads/*');

for ($i = 0; $i < count($search_results); $i++)
{
	$file_json =  file_get_contents($search_results[$i]);
	$object_data = json_decode($file_json, true);
	if ($requested_project_id == $object_data['project_info']['project_id'])
	{
		$project_id = $object_data['project_info']['project_id'];
		$storage_bucket = $object_data['project_info']['storage_bucket'];
		$app_id = $object_data['client'][0]['client_info']['mobilesdk_app_id'];
		$api_key = $object_data['client'][0]['api_key'][0]['current_key'];
		break;
	}	
}
?>

<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link
	href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
	rel="stylesheet"
	integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"
	crossorigin="anonymous"
	/>
	<link rel="stylesheet" type="text/css" href="./css/font-awesome.css">
	<title>Firebase Sign In</title>
	<style>
		body { height: 100vh; }
		#deleteAccountMessageBox,
		#projectValidationWrapper,
		#signInLoader,
		#deleteAccountLoader,
		#emailError,
		#passwordError,
		#submitButton
		{ display: none; }
		#deleteAccountFormCard
		{ display: block; }
	</style>
	<script type="module">
		import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
		import { getAuth, signInWithEmailAndPassword, deleteUser } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

		const projectId = "<?php echo $project_id; ?>";
		const storageBucket = "<?php echo $storage_bucket; ?>";
		const appId = "<?php echo $app_id; ?>";
		const apiKey = "<?php echo $api_key; ?>";

		var app = null;

		const submitButton = document.getElementById("submitButton");
		const signInForm = document.getElementById("signInForm");
		const deleteButton = document.getElementById("deleteButton");
		const closeModal = document.getElementById("closeModal");
		const signInLoader = document.getElementById("signInLoader");
		const errorAlert = document.getElementById("errorAlert");

		signInForm.addEventListener("submit", signIn);
		deleteButton.addEventListener("click", deleteUserAccount);
		closeModal.addEventListener("click", closeConfirmationModal);


	  	if (projectId == '' || projectId == null) {
	  		document.getElementById("projectValidationWrapper").style.display = "block";
	  		submitButton.style.display = "block";
	  		submitButton.disabled = true;
	  		submitInputButton.style.display = "none";
	  	}
	  	else
	  	{
		  	// Initialize Firebase
			const firebaseConfig = {
				apiKey: apiKey,
				projectId: projectId,
				storageBucket: storageBucket,
				appId: appId,
			};
	  		submitButton.disabled = false;
	  		submitButton.style.display = "none";
	  		submitInputButton.style.display = "block";
			app = initializeApp(firebaseConfig);
	  	}

		var UserId = null;

		function validateEmail(email) {
			var re = /\S+@\S+\.\S+/;
			return re.test(email);
		}

		function signIn(event) {
			const email = document.getElementById("email").value;
			const password = document.getElementById("password").value;

			event.preventDefault();
	  		submitButton.style.display = "block";
			submitButton.disabled = true;
			submitInputButton.style.display = "none"
			signInLoader.style.display = "inline-block";
			errorAlert.style.display = "none";


			const auth = getAuth();
			signInWithEmailAndPassword(auth, email, password)
			.then((cred) => {
				console.log(cred);
				UserId = cred.user.uid;
				if (UserId) {
					let myModal = new bootstrap.Modal(document.getElementById('confirmationModal'), {});
					myModal.show();
					submitButton.disabled = false;
			  		submitButton.style.display = "none";
					submitInputButton.style.display = "block"
					signInLoader.style.display = "none";
                }
			})
			.catch((error) => {
				const errorCode = error.code;
				const errorMessage = error.message;
				errorAlert.textContent = "Invalid email or password. Please try again.";
                errorAlert.style.display = "block";
				submitButton.disabled = false;
  				submitButton.style.display = "none";
				submitInputButton.style.display = "block"
				signInLoader.style.display = "none";
			});
		}
       	function closeConfirmationModal() {
			let myModal = new bootstrap.Modal(document.getElementById('confirmationModal'), {});
			myModal.hide();
        }

		async function deleteUserAccount(event) {
			deleteAccountLoader.style.display = "block";
			deleteAccountFormCard.style.display = "none";
			event.preventDefault();
			const auth = getAuth(app);
			const user = auth.currentUser;
			deleteUser(user).then(() => {
				deleteAccountMessageBox.style.display = "block";
				deleteAccountFormCard.style.display = "none";
				deleteAccountLoader.style.display = "none";
				let myModal = new bootstrap.Modal(document.getElementById('confirmationModal'), {});
				myModal.hide();
			}).catch((error) => {
				console.log('del error');
				console.log(error)
			});
		}
	</script>
</head>
<body>
	<div class="d-flex flex-column justify-content-center align-items-center h-100 px-2">
		<div class="alert alert-warning" id="projectValidationWrapper">
			<p class="mb-0 text-center">
				Unble to find the project. Please check and enter a valid project details.
			</p>
		</div>
		<div class="card" id="deleteAccountMessageBox">
			<div class="card-body">
				<div class="alert alert-info mb-0 text-center">
					Your account has been deleted successfully. You can close this window.
				</div>
			</div>
		</div>
		<div class="card w-100 shadow" id="deleteAccountLoader">
			<div class="card-body text-center pt-5 pb-5">
				<i class="fa fa-spinner fa-spin fa-3x" aria-hidden="true"></i>
			</div>
		</div>
		<div class="card" id="deleteAccountFormCard">
			<div class="card-header text-center">
				Delete Your Account
			</div>
			<div class="card-body">
				<form id="signInForm" name="delete_account_form">
					<div class="row">
						<div class="col-lg-12 mb-3">
							<label for="email">Email:</label>
							<input class="form-control" placeholder="Enter your email" type="email" id="email" name="email" required maxlength="320"/>
						</div>
						<div class="col-lg-12 mb-3">
							<label for="password">Password:</label>
							<input class="form-control"  placeholder="Enter your password"  type="password" id="password" name="password" required maxlength="128" minlength="8"/>
						</div>
						<div class="col-lg-12">
							<div class="d-grid gap-2">
								<input class="btn btn-primary" type="submit" value="Delete Account" id="submitInputButton">
								<button class="btn btn-primary" type="submit" id="submitButton">
									<i class="fa fa-spinner fa-spin" aria-hidden="true" id="signInLoader"></i>
									Delete Account
								</button>
							</div>
						</div>
					</div>
				</form>
				<div class="alert alert-danger mt-4 mb-0" role="alert" id="errorAlert" style="display: none;"></div>
				
				<div class="alert alert-danger mt-1 mb-0 p-1" role="alert" id="emailError">
					Email is required.
				</div>
				<div class="alert alert-danger mt-1 mb-0 p-1" role="alert" id="passwordError">
					Password is required.
				</div>



			    <div class="alert alert-info mt-4 mb-0" role="alert">
			    	Please enter your credentials for confirmation.
			    </div>
			</div>
		</div>

		<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="confirmationModalLabel">
							Confirmation
						</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<p class="text-center">
							Are you sure you want to delete your account? You won't be able to undo this action.
						</p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeModal">
							Cancel
						</button>
						<button type="button" class="btn btn-danger" id="deleteButton" data-bs-dismiss="modal">
							Delete
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script
	src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
	integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
	crossorigin="anonymous"
	></script>
</body>
</html>

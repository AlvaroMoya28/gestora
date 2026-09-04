using System.Security.Cryptography;
using System.Text;

namespace Gestora.API.Modules.Auth;

/// <summary>
/// Hash de contraseñas con PBKDF2-HMAC-SHA256. La verificación es de tiempo constante
/// para no filtrar información por diferencias de tiempo de respuesta.
/// </summary>
public static class PasswordHasher
{
    private const string Prefix = "PBKDF2";
    private const int Iterations = 150_000;
    private const int SaltSize = 16;
    private const int KeySize = 32;

    public static (string Hash, string Salt) Hash(string password)
    {
        var salt = RandomNumberGenerator.GetBytes(SaltSize);
        var key = Rfc2898DeriveBytes.Pbkdf2(
            Encoding.UTF8.GetBytes(password), salt, Iterations, HashAlgorithmName.SHA256, KeySize);

        return (Convert.ToBase64String(key), $"{Prefix}${Iterations}${Convert.ToBase64String(salt)}");
    }

    public static bool Verify(string password, string hash, string salt)
    {
        if (string.IsNullOrEmpty(hash) || string.IsNullOrEmpty(salt)) return false;

        var parts = salt.Split('$');
        if (parts.Length != 3 || parts[0] != Prefix || !int.TryParse(parts[1], out var iterations))
            return false;

        byte[] saltBytes, expected;
        try
        {
            saltBytes = Convert.FromBase64String(parts[2]);
            expected = Convert.FromBase64String(hash);
        }
        catch (FormatException) { return false; }

        var actual = Rfc2898DeriveBytes.Pbkdf2(
            Encoding.UTF8.GetBytes(password), saltBytes, iterations, HashAlgorithmName.SHA256, expected.Length);

        return CryptographicOperations.FixedTimeEquals(actual, expected);
    }

    /// <summary>Token opaco aleatorio y seguro para refresh tokens o enlaces de un solo uso.</summary>
    public static string RandomToken(int bytes = 48) =>
        Convert.ToBase64String(RandomNumberGenerator.GetBytes(bytes))
            .Replace("+", "-").Replace("/", "_").TrimEnd('=');
}
